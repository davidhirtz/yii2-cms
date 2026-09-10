<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Controllers;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Override;
use Yii;

final class SiteControllerFunctionalTest extends TestCase
{
    use CmsFixtureTrait;
    use FunctionalTestTrait;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        //        Yii::$container->setDefinitions([
        //            Gallery::class => [
        //                'viewFile' => '@tests/data/views/site/widgets/_assets',
        //            ],
        //            SiteController::class => [
        //                'layout' => '@tests/data/views/layouts/main',
        //            ],
        //            Sections::class => [
        //                'viewFile' => '@tests/data/views/site/_sections',
        //            ],
        //        ]);
    }

    public function testHomepage(): void
    {
        $entry = Entry::create();
        $entry->name = 'Homepage';
        $entry->slug = $entry::getModule()->entryIndexSlug;
        $entry->insert();

        $this->open('/');

        self::assertResponseIsSuccessful();
        self::assertPageTitleSame($entry->name);
    }

    public function testEnabledEntry(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $urlManager = Yii::$app->getUrlManager();

        $this->open($urlManager->createUrl($entry->getRoute()));

        self::assertResponseIsSuccessful();
        self::assertResponseNotHasHeader('x-robots-tag');
        self::assertPageTitleSame($entry->name);

        $html = self::$crawler->html();

        foreach ($entry->sections as $section) {
            if ($section->isEnabled()) {
                foreach ($section->getVisibleAssets() as $asset) {
                    if ($asset->isEnabled()) {
                        self::assertStringContainsString($asset->file->getUrl(), $html);
                    } else {
                        self::assertStringNotContainsString($asset->file->getUrl(), $html);
                    }
                }

                if ($content = $section->getVisibleAttribute('content')) {
                    self::assertStringContainsString($content, $html);
                }
            } elseif ($content = $section->getVisibleAttribute('content')) {
                self::assertStringNotContainsString($content, $html);
            }
        }

        /** @var Asset $asset */
        $asset = current(array_filter($entry->assets, fn (Asset $asset) => $asset->type === Asset::TYPE_META_IMAGE));
        $url = $urlManager->createAbsoluteUrl($asset->file->getUrl());

        self::assertStringContainsString('<link href="' . $url . '" rel="image_src">', $html);
    }

    public function testDraftEntry(): void
    {
        $entry = $this->getEntryFromFixture('page-draft');
        $urlManager = Yii::$app->getUrlManager();

        $this->open($urlManager->createUrl($entry->getRoute()));
        self::assertResponseStatusCodeSame(404);

        $this->open($urlManager->createDraftUrl($entry->getRoute()));
        self::assertResponseIsSuccessful();
        self::assertPageTitleSame($entry->name);
        self::assertResponseHeaderSame('x-robots-tag', 'none');
    }

    public function testDisabledEntry(): void
    {
        $entry = $this->getEntryFromFixture('page-disabled');
        $urlManager = Yii::$app->getUrlManager();

        $this->open($urlManager->createUrl(['/cms/site/view', 'slug' => $entry->getFormattedSlug()]));
        self::assertResponseStatusCodeSame(404);
    }

    public function testEntrySlugWithTrailingSlash(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $urlManager = Yii::$app->getUrlManager();

        $this->open($urlManager->createUrl($entry->getRoute()) . '/');
        self::assertCurrentUrlEquals($urlManager->createUrl($entry->getRoute()));
    }

    public function testUnknownSlug(): void
    {
        $this->open('/there-is-no-such-page');
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * The same action resolves an entry or a category, depending only on what the permalink points at.
     */
    public function testCategoryIsResolvedByTheSameAction(): void
    {
        Category::getModule()->enableCategoryUrls = true;

        $category = $this->createCategory('news');

        $this->open('/news');

        self::assertResponseIsSuccessful();
        self::assertPageTitleSame($category->name);
    }

    public function testCategoryIsNotResolvedWhenCategoryUrlsAreDisabled(): void
    {
        Category::getModule()->enableCategoryUrls = true;
        $this->createCategory('news');

        Category::getModule()->enableCategoryUrls = false;

        $this->open('/news');
        self::assertResponseStatusCodeSame(404);
    }

    public function testNestedCategoryResolvesUnderItsOwnSlug(): void
    {
        Category::getModule()->enableCategoryUrls = true;

        $parent = $this->createCategory('news');
        $child = $this->createCategory('sport', $parent);

        // A category slug is a single globally-unique segment, so a child resolves at its own slug, not a path.
        $this->open('/sport');

        self::assertResponseIsSuccessful();
        self::assertPageTitleSame($child->name);
    }

    protected function createCategory(string $slug, ?Category $parent = null): Category
    {
        $module = Category::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;

        $category = Category::create();
        $category->name = ucfirst($slug);
        $category->slug = $slug;
        $category->parent_id = $parent?->id;

        self::assertTrue($category->save(), implode(' ', $category->getErrorSummary(true)));

        return $category;
    }
}
