<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Collections;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

class CategoryCollectionTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;

        CategoryCollection::reset();
    }

    #[Override]
    protected function tearDown(): void
    {
        CategoryCollection::reset();
        parent::tearDown();
    }

    /**
     * The records a request loaded must never reach the next one, so `Bootstrap` drops them — a reset that only
     * ran in the tests would leave a resident application serving them forever.
     */
    public function testTheCategoriesDoNotOutliveTheApplication(): void
    {
        $categories = CategoryCollection::getAll();
        $category = reset($categories);
        self::assertInstanceOf(Category::class, $category);

        $this->reloadApplication();

        $reloaded = CategoryCollection::getAll();
        self::assertNotSame($category, reset($reloaded));
    }

    public function testEveryCategoryIsLoadedOnce(): void
    {
        self::assertEqualsCanonicalizing([1, 2, 3], array_keys(CategoryCollection::getAll()));

        $queries = $this->countQueries(function (): void {
            CategoryCollection::getAll();
            CategoryCollection::getAll();
        });

        self::assertSame(0, $queries);
    }

    /**
     * The query result is cached too, so a reload only reaches the database with the query cache turned off.
     */
    public function testTheCollectionCanBeAskedToReload(): void
    {
        TestEntry::getModule()->categoryCachedQueryDuration = false;

        CategoryCollection::getAll(true);

        $queries = $this->countQueries(function (): void {
            CategoryCollection::getAll(true);
        });

        self::assertGreaterThan(0, $queries);
    }

    public function testTheAncestorsAreTheOnesItSitsInside(): void
    {
        $child = $this->getCategoryFromFixture('child-1');

        self::assertSame([1], array_keys(CategoryCollection::getAncestors($child)));
        self::assertSame([], CategoryCollection::getAncestors($this->getCategoryFromFixture('root-1')));
    }

    public function testTheChildrenAreTheLevelBelow(): void
    {
        self::assertSame([3], array_keys(CategoryCollection::getChildren($this->getCategoryFromFixture('root-1'))));
        self::assertSame([], CategoryCollection::getChildren($this->getCategoryFromFixture('root-2')));
    }

    public function testTheDescendantsAreEverythingBelow(): void
    {
        self::assertSame([3], array_keys(CategoryCollection::getDescendants($this->getCategoryFromFixture('root-1'))));
        self::assertSame([], CategoryCollection::getDescendants($this->getCategoryFromFixture('child-1')));
    }

    public function testTheCategoriesOfAnEntryComeFromItsOwnIds(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        self::assertSame([1, 3], array_keys(CategoryCollection::getByEntry($entry)));
        self::assertSame([], CategoryCollection::getByEntry($this->getEntryFromFixture('post-1')));
    }

    public function testASlugResolvesToItsCategory(): void
    {
        self::assertSame(1, CategoryCollection::getBySlug('root-1')?->id);
        self::assertNull(CategoryCollection::getBySlug('nothing-here'));
        self::assertNull(CategoryCollection::getBySlug(''));
    }

    /**
     * A nested category is addressed by its whole path, so the same slug can sit under two parents.
     */
    public function testANestedSlugIsResolvedPartByPart(): void
    {
        self::assertSame(3, CategoryCollection::getBySlug('root-1/child-1')?->id);

        // the child does not sit under the other root
        self::assertNull(CategoryCollection::getBySlug('root-2/child-1'));
    }

    public function testASlugOnlyMatchesAtItsOwnLevel(): void
    {
        self::assertNull(CategoryCollection::getBySlug('child-1'));
        self::assertSame(3, CategoryCollection::getBySlug('child-1', 1)?->id);
    }

    public function testInvalidatingTheCacheReloads(): void
    {
        CategoryCollection::getAll();

        $category = Category::create();
        $category->loadDefaultValues();
        $category->status = Category::STATUS_ENABLED;
        $category->name = 'Fresh';
        $category->slug = 'fresh';

        self::assertTrue($category->insert(), print_r($category->getErrors(), true));

        // the model invalidates the cache itself on save
        self::assertArrayHasKey($category->id, CategoryCollection::getAll());
    }
}
