<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Search;
use Hirtz\Skeleton\Search\Search as SearchComponent;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Yii;
use yii\db\Query;

class EntryTenantTest extends TestCase
{
    public function testCreateIndexEntry(): void
    {
        $tenant = TenantCollection::getDefault();

        $entry = TestEntry::create();
        $entry->name = 'Home';
        $entry->slug = $entry::getModule()->entryIndexSlug ?: null;
        $entry->populateTenantRelation($tenant);

        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));
        self::assertTrue($entry->isIndex());
        self::assertEquals($tenant->id, $entry->tenant_id);

        $tenant->refresh();

        self::assertEquals(1, $tenant->getAttribute('entry_count'));
    }

    /**
     * An empty value resolves to the default tenant, so nothing that creates entries has to know about tenants.
     */
    public function testCreateEntryWithoutATenant(): void
    {
        $entry = TestEntry::create();
        $entry->name = 'No tenant';

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));
        self::assertSame(TenantCollection::getDefault()->id, $entry->tenant_id);
    }

    public function testCreateEntryValidationErrors(): void
    {
        $entry = TestEntry::create();
        $entry->name = 'Invalid';
        $entry->tenant_id = 12345;

        self::assertFalse($entry->save());
        self::assertNotEmpty($entry->getErrors('tenant_id'));
    }

    public function testParentFromAnotherTenantIsRejected(): void
    {
        TestEntry::getModule()->enableNestedEntries = true;

        $parent = $this->createEntry('parent', TenantCollection::getDefault());
        $child = TestEntry::create();
        $child->name = 'Child';
        $child->parent_id = $parent->id;
        $child->populateTenantRelation($this->createTenant());

        self::assertFalse($child->save());
        self::assertNotEmpty($child->getErrors('parent_id'));
    }

    public function testUpdateAndDeleteEntry(): void
    {
        $tenant = TenantCollection::getDefault();

        $entry = TestEntry::create();
        $entry->name = 'Test';
        $entry->populateTenantRelation($tenant);

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        $section = Section::create();
        $section->populateEntryRelation($entry);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        self::assertEquals('https://www.domain.localhost/test', Url::toRoute($entry->getRoute() ?: []));

        $tenant->refresh();
        self::assertEquals(1, $tenant->getAttribute('entry_count'));

        $newTenant = $this->createTenant();

        $entry->tenant_id = $newTenant->id;

        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));
        self::assertEquals($newTenant->id, $entry->tenant_id);
        self::assertEquals('https://www.new-domain.localhost/test', Url::toRoute($entry->getRoute() ?: []));

        $tenant->refresh();
        self::assertEquals(0, $tenant->getAttribute('entry_count'));

        $newTenant->refresh();
        self::assertEquals(1, $newTenant->getAttribute('entry_count'));

        self::assertFalse($newTenant->delete());
        self::assertContains('This tenant cannot be deleted because it is linked to other relations.', $newTenant->getFirstErrors());

        self::assertEquals(1, $entry->delete());

        $newTenant->refresh();
        self::assertEquals(0, $newTenant->getAttribute('entry_count'));
        self::assertEquals(1, $newTenant->delete());
    }

    /**
     * A descendant follows its ancestor's tenant, and so does the permalink that carries a copy of it.
     */
    public function testChangingTheTenantMovesDescendantsAndTheirPermalinks(): void
    {
        TestEntry::getModule()->enableNestedEntries = true;

        $parent = $this->createEntry('parent', TenantCollection::getDefault());
        $child = $this->createEntry('child', TenantCollection::getDefault(), $parent);

        $newTenant = $this->createTenant();

        $parent->refresh();
        $parent->tenant_id = $newTenant->id;

        self::assertTrue($parent->save(), implode(' ', $parent->getErrorSummary(true)));

        $child->refresh();
        self::assertSame($newTenant->id, $child->tenant_id);
        self::assertSame($newTenant->id, $child->getPermalink()->tenant_id);

        $parent->refresh();
        self::assertSame($newTenant->id, $parent->getPermalink()->tenant_id);
    }

    /**
     * Neither the descendants nor the sections are saved when the tenant changes, but their search documents carry it.
     */
    public function testChangingTheTenantMovesTheSearchDocumentsOfSectionsAndDescendants(): void
    {
        TestEntry::getModule()->enableNestedEntries = true;

        $parent = $this->createEntry('parent', TenantCollection::getDefault());
        $child = $this->createEntry('child', TenantCollection::getDefault(), $parent);

        $parentSection = $this->createSection($parent);
        $childSection = $this->createSection($child);

        $newTenant = $this->createTenant();

        $parent->refresh();
        $parent->tenant_id = $newTenant->id;

        self::assertTrue($parent->save(), implode(' ', $parent->getErrorSummary(true)));

        foreach ([$parent, $child, $parentSection, $childSection] as $record) {
            self::assertSame([$newTenant->id], $this->getSearchTenantIds($record), $record::class);
        }
    }

    public function testMovingASectionToAnEntryOfAnotherTenantMovesItsSearchDocuments(): void
    {
        $entry = $this->createEntry('entry', TenantCollection::getDefault());
        $section = $this->createSection($entry);

        $newTenant = $this->createTenant();
        $other = $this->createEntry('other', $newTenant);

        $section->populateEntryRelation($other);
        self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));

        self::assertSame([$newTenant->id], $this->getSearchTenantIds($section));
    }

    /**
     * @return list<int>
     */
    protected function getSearchTenantIds(TestEntry|Section $record): array
    {
        $tenantIds = (new Query())
            ->select(['tenant_id'])
            ->distinct()
            ->from(Search::tableName())
            ->where([
                'model_class' => SearchComponent::getComponent()->getRegisteredClass($record::class),
                'model_id' => $record->id,
            ])
            ->column();

        return array_values(array_map(intval(...), $tenantIds));
    }

    protected function createSection(TestEntry $entry): Section
    {
        $section = Section::create();
        $section->loadDefaultValues();
        $section->status = Section::STATUS_ENABLED;
        $section->type = Section::TYPE_DEFAULT;
        $section->name = 'Section of ' . $entry->name;
        $section->populateEntryRelation($entry);

        self::assertTrue($section->insert(), implode(' ', $section->getErrorSummary(true)));

        return $section;
    }

    protected function createTenant(): Tenant
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = 'New Tenant';
        $tenant->language = Yii::$app->sourceLanguage;
        $tenant->url = 'https://www.new-domain.localhost';

        self::assertTrue($tenant->save(), implode(' ', $tenant->getErrorSummary(true)));

        return $tenant;
    }

    protected function createEntry(string $slug, Tenant $tenant, ?TestEntry $parent = null): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = ucfirst($slug);
        $entry->slug = $slug;
        $entry->parent_id = $parent?->id;
        $entry->populateTenantRelation($tenant);

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }
}
