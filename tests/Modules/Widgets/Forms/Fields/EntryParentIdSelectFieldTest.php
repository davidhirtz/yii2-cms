<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Types\EntryType;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Yii;

class EntryParentIdSelectFieldTest extends TestCase
{
    public function testTheNameOfAParentIsEncodedOnce(): void
    {
        TestEntry::getModule()->enableNestedEntries = true;

        $parent = TestEntry::create();
        $parent->loadDefaultValues();
        $parent->status = TestEntry::STATUS_ENABLED;
        $parent->type = TestEntry::TYPE_PAGE;
        $parent->name = 'A & B';
        $parent->slug = 'a-and-b';
        $parent->populateTenantRelation(TenantCollection::getDefault());

        self::assertTrue($parent->insert(), print_r($parent->getErrors(), true));

        $entry = TestEntry::create();
        $entry->tenant_id = $parent->tenant_id;

        $html = EntryParentIdSelectField::make()
            ->model($entry)
            ->render();

        self::assertStringContainsString('>A &amp; B</option>', $html);
    }

    /**
     * A parent whose type stopped allowing subentries stays the child's option, or the next save of the child would
     * move it to the root.
     */
    public function testTheCurrentParentStaysOfferedAfterItsTypeChanged(): void
    {
        Entry::getModule()->enableNestedEntries = true;

        Yii::$container->set(Entry::class, [
            'types' => fn (): array => [
                EntryType::make(1)->name('Page'),
                EntryType::make(2)->name('Leaf')->allowDescendants(false),
            ],
        ]);

        DefinitionRegistry::reset();

        $parent = $this->createEntry('Über uns', 'about');
        $other = $this->createEntry('Other', 'other');
        $other->updateAttributes(['type' => 2]);

        $child = $this->createEntry('Team', 'team');
        $child->updateAttributes(['parent_id' => $parent->id]);
        $parent->updateAttributes(['type' => 2]);

        $html = EntryParentIdSelectField::make()
            ->model(Entry::findOne($child->id) ?? self::fail('The child is missing.'))
            ->render();

        self::assertStringContainsString('>Über uns</option>', $html);
        self::assertStringNotContainsString('>Other</option>', $html);
    }

    private function createEntry(string $name, string $slug): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = 1;
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->populateTenantRelation(TenantCollection::getDefault());

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }
}
