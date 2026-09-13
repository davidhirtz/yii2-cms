<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Trail;
use Override;

class EntryCategoryTest extends TestCase
{
    use CmsFixtureTrait;

    /**
     * `Module::init()` cascades the three flags, and it has already run by the time a test reaches the module, so
     * a test that only sets `enableCategories` still gets no inheritance.
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;
        $module->inheritNestedCategories = true;
    }

    public function testTheJunctionUpdatesBothDenormalisedCounters(): void
    {
        $entry = $this->getEntryFromFixture('page-draft');
        $category = $this->getCategoryFromFixture('root-2');

        $this->createEntryCategory($entry, $category);

        $junctions = (int)EntryCategory::find()->where(['category_id' => $category->id])->count();

        self::assertSame($junctions, Category::findOne($category->id)->entry_count);
        self::assertContains($category->id, TestEntry::findOne($entry->id)->category_ids);
    }

    public function testTheJunctionIsAddedToEveryAncestorToo(): void
    {
        $entry = $this->getEntryFromFixture('post-1');
        $child = $this->getCategoryFromFixture('child-1');

        $this->createEntryCategory($entry, $child);

        $categoryIds = TestEntry::findOne($entry->id)->category_ids;

        self::assertContains($child->id, $categoryIds);
        self::assertContains(
            $this->getCategoryFromFixture('root-1')->id,
            $categoryIds,
            'The ancestor category was not inherited.'
        );

        self::assertSame(
            2,
            (int)EntryCategory::find()->where(['entry_id' => $entry->id])->count()
        );
    }

    public function testTheAncestorsAreNotInheritedWhileTheModuleSaysNotTo(): void
    {
        TestEntry::getModule()->inheritNestedCategories = false;

        $entry = $this->getEntryFromFixture('post-1');
        $child = $this->getCategoryFromFixture('child-1');

        $this->createEntryCategory($entry, $child);

        self::assertSame([$child->id], TestEntry::findOne($entry->id)->category_ids);
    }

    /**
     * Removing a category removes the entry from everything below it too, since those were inherited.
     */
    public function testDeletingAJunctionRemovesTheDescendantsWithIt(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $root = $this->getCategoryFromFixture('root-1');
        $child = $this->getCategoryFromFixture('child-1');

        // the fixture has the entry in both, as the inheritance would have left it
        self::assertNotNull(EntryCategory::findOne(['entry_id' => $entry->id, 'category_id' => $child->id]));

        $junction = EntryCategory::findOne(['entry_id' => $entry->id, 'category_id' => $root->id]);
        $junction->populateEntryRelation($entry);
        $junction->populateCategoryRelation($root);

        self::assertSame(1, $junction->delete());

        self::assertNull(EntryCategory::findOne(['entry_id' => $entry->id, 'category_id' => $root->id]));
        self::assertNull(
            EntryCategory::findOne(['entry_id' => $entry->id, 'category_id' => $child->id]),
            'The inherited junction of the descendant category survived.'
        );

        self::assertNull(TestEntry::findOne($entry->id)->category_ids);
        self::assertSame(0, Category::findOne($child->id)->entry_count);
    }

    /**
     * The cascade owns the whole branch, so each junction below the one being deleted is a batch delete: the
     * entry's `category_ids` are recalculated once, by the junction the cascade started from.
     */
    public function testTheCascadeRecalculatesTheEntryOnlyOnce(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $root = $this->getCategoryFromFixture('root-1');

        $junction = EntryCategory::findOne(['entry_id' => $entry->id, 'category_id' => $root->id]);
        $junction->populateEntryRelation($entry);
        $junction->populateCategoryRelation($root);

        $updates = 0;

        $entry->on(TestEntry::EVENT_AFTER_UPDATE, function () use (&$updates): void {
            ++$updates;
        });

        $junction->delete();

        self::assertSame(1, $updates);
    }

    public function testTheSameCategoryIsNotAddedTwice(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $category = $this->getCategoryFromFixture('root-1');

        $junction = EntryCategory::create();
        $junction->populateEntryRelation($entry);
        $junction->populateCategoryRelation($category);

        self::assertFalse($junction->insert());
        self::assertArrayHasKey('entry_id', $junction->getErrors());
    }

    public function testAJunctionIsRefusedWhileCategoriesAreOff(): void
    {
        TestEntry::getModule()->enableCategories = false;

        $junction = EntryCategory::create();
        $junction->populateEntryRelation($this->getEntryFromFixture('post-1'));
        $junction->populateCategoryRelation($this->getCategoryFromFixture('root-2'));

        self::assertFalse($junction->insert());
        self::assertArrayHasKey('entry_id', $junction->getErrors());
    }

    public function testThePositionContinuesFromTheCategorysHighestOne(): void
    {
        $category = $this->getCategoryFromFixture('root-1');
        $junction = $this->createEntryCategory($this->getEntryFromFixture('post-1'), $category);

        self::assertSame(3, $junction->position);
    }

    public function testTheJunctionIsFiledUnderBothItsParents(): void
    {
        $entry = $this->getEntryFromFixture('page-draft');
        $category = $this->getCategoryFromFixture('root-2');

        $junction = $this->createEntryCategory($entry, $category);

        self::assertSame([$entry, $category], $junction->getTrailParents());

        $trail = Trail::find()
            ->where(['model_class' => EntryCategory::class])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        self::assertNotNull($trail);
        self::assertSame(Trail::TYPE_CREATE, $trail->type);
    }

    private function createEntryCategory(TestEntry $entry, Category $category): EntryCategory
    {
        $junction = EntryCategory::create();
        $junction->populateEntryRelation($entry);
        $junction->populateCategoryRelation($category);

        self::assertTrue($junction->insert(), print_r($junction->getErrors(), true));

        return $junction;
    }
}
