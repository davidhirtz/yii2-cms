<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Sitemap;

use Hirtz\Cms\Sitemap\EntrySitemap;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Override;
use Yii;

class EntryImageSitemapTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->set('sitemap', ['class' => Sitemap::class]);
        Yii::$app->getUrlManager()->i18nUrl = false;
    }

    public function testTheImagesAreOmittedByDefault(): void
    {
        foreach ($this->createSitemap()->generateUrls() as $url) {
            self::assertIsArray($url);
            self::assertArrayNotHasKey('images', $url);
        }
    }

    public function testAnEntryCarriesItsAssetImages(): void
    {
        $urls = $this->createSitemap(['enableImages' => true])->generateUrls();
        $images = array_merge(...array_column($urls, 'images'));

        self::assertNotEmpty($images);
        self::assertContains('Alt Text 1', array_column($images, 'title'));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createSitemap(array $config = []): EntrySitemap
    {
        return Yii::$container->get(EntrySitemap::class, [], $config);
    }
}
