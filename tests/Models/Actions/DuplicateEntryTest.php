<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\DuplicateEntry;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

/**
 * The records are built here rather than taken from the fixture: the fixture's sections carry `TestSection` types,
 * and everything the product loads through `Entry::getSections()` is a base `Section`, which refuses them.
 */
class DuplicateEntryTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;
        $module->inheritNestedCategories = true;
        $module->enableNestedEntries = true;
    }

    public function testTheDuplicateIsADraftWithItsOwnSlug(): void
    {
        $entry = $this->createEntry('Original', 'original');
        $duplicate = DuplicateEntry::create(['entry' => $entry]);

        self::assertFalse($duplicate->getIsNewRecord());
        self::assertNotSame($entry->id, $duplicate->id);

        self::assertSame('Original', $duplicate->name);
        self::assertSame(Entry::STATUS_DRAFT, $duplicate->status);
        self::assertNotSame($entry->slug, $duplicate->slug);
    }

    public function testTheGivenAttributesWinOverTheDefaults(): void
    {
        $entry = $this->createEntry('Original', 'original');

        $duplicate = DuplicateEntry::create([
            'entry' => $entry,
            'attributes' => [
                'status' => Entry::STATUS_ENABLED,
                'name' => 'Renamed',
            ],
        ]);

        self::assertSame(Entry::STATUS_ENABLED, $duplicate->status);
        self::assertSame('Renamed', $duplicate->name);
    }

    public function testTheSectionsAreCopiedInOrder(): void
    {
        $entry = $this->createEntry('Original', 'original');

        $this->createSection($entry, 'First');
        $this->createSection($entry, 'Second');

        $entry->refresh();
        self::assertSame(2, $entry->section_count);

        $duplicate = DuplicateEntry::create(['entry' => $entry]);

        $names = array_values(array_map(
            fn (Section $section): string => (string)$section->name,
            $duplicate->getSections()->all()
        ));

        self::assertSame(['First', 'Second'], $names);
        self::assertSame(2, Entry::findOne($duplicate->id)->section_count);

        // the original keeps its own
        self::assertSame(2, (int)Section::find()->where(['entry_id' => $entry->id])->count());
    }

    public function testTheChildrenAreCopiedUnderTheDuplicate(): void
    {
        $entry = $this->createEntry('Parent', 'parent');

        $this->createEntry('First child', 'first-child', $entry);
        $this->createEntry('Second child', 'second-child', $entry);

        $entry->refresh();
        self::assertSame(2, $entry->entry_count);

        $duplicate = DuplicateEntry::create(['entry' => $entry]);

        $children = Entry::find()
            ->where(['parent_id' => $duplicate->id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        self::assertCount(2, $children);
        self::assertSame(['First child', 'Second child'], array_map(fn (Entry $child) => $child->name, $children));

        // a copied child keeps the status it had, rather than falling back to the draft default
        self::assertSame(Entry::STATUS_ENABLED, $children[0]->status);
    }

    public function testTheCategoriesAreCopiedWithoutRecountingPerJunction(): void
    {
        $entry = $this->createEntry('Original', 'original');
        $category = $this->getCategoryFromFixture('root-2');

        $junction = EntryCategory::create();
        $junction->populateEntryRelation($entry);
        $junction->populateCategoryRelation($category);

        self::assertTrue($junction->insert());

        $entry->refresh();

        $duplicate = DuplicateEntry::create(['entry' => $entry]);

        self::assertNotNull(EntryCategory::findOne(['entry_id' => $duplicate->id, 'category_id' => $category->id]));
        self::assertSame($entry->category_ids, $duplicate->category_ids);

        // the category's own count is recalculated from the junctions, not incremented per copy
        self::assertSame(
            (int)EntryCategory::find()->where(['category_id' => $category->id])->count(),
            Category::findOne($category->id)->entry_count
        );
    }

    public function testTheDuplicateCanBeMovedUnderAnotherParent(): void
    {
        $entry = $this->createEntry('Original', 'original');
        $parent = $this->createEntry('New parent', 'new-parent');

        $duplicate = DuplicateEntry::create([
            'entry' => $entry,
            'parent' => $parent,
        ]);

        self::assertSame($parent->id, $duplicate->parent_id);
        self::assertSame(1, Entry::findOne($parent->id)->entry_count);
    }

    /**
     * A nested duplication passes `false` so only the outermost call touches the parent's counters.
     */
    public function testANewParentIsIgnoredWhileItIsUnsaved(): void
    {
        $entry = $this->createEntry('Original', 'original', $this->createEntry('Parent', 'parent'));

        $duplicate = DuplicateEntry::create([
            'entry' => $entry,
            'parent' => TestEntry::create(),
        ]);

        self::assertSame($entry->parent_id, $duplicate->parent_id);
    }

    private function createEntry(string $name, string $slug, ?Entry $parent = null): TestEntry
    {
        $entry = TestEntry::create();
        $entry->loadDefaultValues();
        $entry->status = TestEntry::STATUS_ENABLED;
        $entry->type = TestEntry::TYPE_PAGE;
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->populateParentRelation($parent);

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    private function createSection(Entry $entry, string $name): Section
    {
        $section = Section::create();
        $section->loadDefaultValues();
        $section->status = Section::STATUS_ENABLED;
        $section->type = Section::TYPE_DEFAULT;
        $section->name = $name;
        $section->populateEntryRelation($entry);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        return $section;
    }
}
