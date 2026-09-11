<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Yii;

class EntryTenantTest extends TestCase
{
    public function testCreateIndexEntry(): void
    {
        $tenant = TenantCollection::getDefault();

        $entry = TestEntry::create();
        $entry->name = 'Home';
        $entry->slug = $entry::getModule()->entryIndexSlug;
        $entry->populateTenantRelation($tenant);

        self::assertTrue($entry->save());
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

        self::assertTrue($entry->insert());

        $section = Section::create();
        $section->populateEntryRelation($entry);

        self::assertTrue($section->insert());

        self::assertEquals('https://www.domain.localhost/test', Url::toRoute($entry->getRoute()));

        $tenant->refresh();
        self::assertEquals(1, $tenant->getAttribute('entry_count'));

        $newTenant = $this->createTenant();

        $entry->tenant_id = $newTenant->id;

        self::assertTrue($entry->save());
        self::assertEquals($newTenant->id, $entry->tenant_id);
        self::assertEquals('https://www.new-domain.localhost/test', Url::toRoute($entry->getRoute()));

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
