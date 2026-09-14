<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Sitemap;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Sitemap\CategorySitemap;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Override;
use Yii;

class CategorySitemapTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->set('sitemap', ['class' => Sitemap::class]);
        Yii::$app->getUrlManager()->i18nUrl = false;
    }

    public function testEveryEnabledCategoryGetsAUrl(): void
    {
        $sitemap = $this->createSitemap();
        $slugs = array_column(array_column($sitemap->generateUrls(), 'loc'), 'category');

        self::assertEqualsCanonicalizing(['root-1', 'root-2', 'child-1'], $slugs);
        self::assertSame(3, $sitemap->getRecordCount());
    }

    /**
     * The status filter belongs in the query, or the record count the index pages by would count records that never
     * produce a URL.
     */
    public function testADisabledCategoryIsLeftOutOfTheCountToo(): void
    {
        $category = $this->getCategoryFromFixture('root-2');
        $category->status = Category::STATUS_DISABLED;

        self::assertTrue($category->update() !== false);

        $sitemap = $this->createSitemap();

        self::assertCount(2, $sitemap->generateUrls());
        self::assertSame(2, $sitemap->getRecordCount());
    }

    private function createSitemap(): CategorySitemap
    {
        /** @var CategorySitemap $sitemap */
        $sitemap = Yii::createObject(CategorySitemap::class);
        return $sitemap;
    }
}
