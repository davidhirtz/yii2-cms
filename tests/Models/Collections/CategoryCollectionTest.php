<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Collections;

use Hirtz\Cms\Models\Actions\ReorderCategories;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Controllers\McpController;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\caching\ArrayCache;
use yii\caching\TagDependency;

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
     * `Bootstrap` drops the records a request loaded, since a reset only the tests ran would leave a resident
     * application serving them.
     */
    public function testTheCategoriesDoNotOutliveTheApplication(): void
    {
        $categories = CategoryCollection::getAll();
        $category = reset($categories);
        self::assertNotFalse($category);
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

    /**
     * A page a project controller renders lists published categories, as the cms controller's pages do; the admin
     * and MCP list every status (#686).
     */
    public function testTheWebsiteListsOnlyPublishedCategories(): void
    {
        Category::updateAll(['status' => Category::STATUS_DISABLED], ['id' => 2]);
        CategoryCollection::reset();

        Yii::$app->controller = new Controller('page', Yii::$app);
        self::assertArrayNotHasKey(2, CategoryCollection::getAll());

        $admin = Yii::$app->createController('admin/cms/category/index')[0] ?? null;
        self::assertInstanceOf(Controller::class, $admin);
        Yii::$app->controller = $admin;
        self::assertArrayHasKey(2, CategoryCollection::getAll());

        Yii::$app->controller = new McpController('mcp', Yii::$app);
        self::assertArrayHasKey(2, CategoryCollection::getAll());
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

    public function testANestedSlugIsResolvedPartByPart(): void
    {
        self::assertSame(3, CategoryCollection::getBySlug('root-1/child-1')?->id);

        // the child does not sit under the other root
        self::assertNull(CategoryCollection::getBySlug('root-2/child-1'));
    }

    public function testAPathWithAnUnknownPartResolvesToNothing(): void
    {
        self::assertNull(CategoryCollection::getBySlug('garbage/root-1'));
        self::assertNull(CategoryCollection::getBySlug('root-1/garbage/child-1'));
        self::assertNull(CategoryCollection::getBySlug('root-1/garbage'));
        self::assertNull(CategoryCollection::getBySlug('child-1/root-1'));
    }

    /**
     * A slug is unique across every level, and a nested category's route names its own slug alone.
     */
    public function testANestedCategoryResolvesByItsOwnSlug(): void
    {
        $category = CategoryCollection::getBySlug('child-1');

        self::assertSame(3, $category?->id);
        self::assertSame(['/cms/site/index', 'category' => 'child-1'], $category->getRoute());
        self::assertSame(3, CategoryCollection::getBySlug('child-1', 1)?->id);
        self::assertNull(CategoryCollection::getBySlug('child-1', 2));
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

    public function testDeletingACategoryReloads(): void
    {
        self::assertArrayHasKey(2, CategoryCollection::getAll());

        self::assertNotFalse($this->getCategoryFromFixture('root-2')->delete());

        CategoryCollection::reset();
        self::assertArrayNotHasKey(2, CategoryCollection::getAll());
    }

    /**
     * Invalidated before the commit, a request in between would cache the old tree again.
     */
    public function testReorderingTheCategoriesReloadsOnceCommitted(): void
    {
        $cache = new class () extends ArrayCache {
            public ?int $invalidatedAt = null;

            #[Override]
            protected function setValue($key, $value, $duration): bool
            {
                if ($key === $this->buildKey([TagDependency::class, CategoryCollection::CACHE_KEY])) {
                    $this->invalidatedAt = Yii::$app->getDb()->getTransaction()?->getLevel();
                }

                return parent::setValue($key, $value, $duration);
            }
        };

        Yii::$app->set('cache', $cache);
        $level = Yii::$app->getDb()->getTransaction()?->getLevel();

        $lft = CategoryCollection::getAll()[2]->lft;

        self::assertGreaterThan(0, (new ReorderCategories(null, [2, 1]))->run());

        self::assertSame($level, $cache->invalidatedAt);

        CategoryCollection::reset();
        self::assertNotSame($lft, CategoryCollection::getAll()[2]->lft);
    }
}
