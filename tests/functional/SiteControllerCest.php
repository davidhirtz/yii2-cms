<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\functional;

use davidhirtz\yii2\cms\controllers\SiteController;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\tests\support\fixtures\traits\CmsFixturesTrait;
use davidhirtz\yii2\cms\tests\support\FunctionalTester;
use davidhirtz\yii2\cms\widgets\Gallery;
use davidhirtz\yii2\cms\widgets\Sections;
use davidhirtz\yii2\skeleton\codeception\functional\BaseCest;
use Yii;

class SiteControllerCest extends BaseCest
{
    use CmsFixturesTrait;

    #[\Override]
    public function _before(): void
    {
        Yii::$container->setDefinitions([
            Gallery::class => [
                'viewFile' => '@tests/data/views/site/widgets/_assets',
            ],
            SiteController::class => [
                'layout' => '@tests/data/views/layouts/main',
            ],
            Sections::class => [
                'viewFile' => '@tests/data/views/site/_sections',
            ],
        ]);

        parent::_before();
    }

    public function checkHomepage(FunctionalTester $I): void
    {
        $entry = Entry::create();
        $entry->name = 'Homepage';
        $entry->slug = $entry::getModule()->entryIndexSlug;
        $entry->insert();

        $I->amOnPage('/');
        $I->canSeeInTitle($entry->name);
    }

    public function checkEnabledEntry(FunctionalTester $I): void
    {
        $entry = $I->grabEntryFixture('page-enabled');
        $urlManager = Yii::$app->getUrlManager();

        $I->amOnPage($urlManager->createUrl($entry->getRoute()));

        $I->seeResponseCodeIs(200);
        $I->haveHttpHeader('x-robots-tag', '');
        $I->seeInTitle($entry->name);

        foreach ($entry->sections as $section) {
            if ($section->isEnabled()) {
                foreach ($section->getVisibleAssets() as $asset) {
                    if ($asset->isEnabled()) {
                        $I->seeInSource($asset->file->getUrl());
                    } else {
                        $I->dontSeeInSource($asset->file->getUrl());
                    }
                }

                if ($content = $section->getVisibleAttribute('content')) {
                    $I->seeInSource($content);
                }
            } elseif ($content = $section->getVisibleAttribute('content')) {
                $I->dontSeeInSource($content);
            }
        }

        /** @var Asset $asset */
        $asset = current(array_filter($entry->assets, fn (Asset $asset) => $asset->type === Asset::TYPE_META_IMAGE));
        $url = $urlManager->createAbsoluteUrl($asset->file->getUrl());

        $I->seeInSource('<link href="' . $url . '" rel="image_src">');
    }

    public function checkDraftEntry(FunctionalTester $I): void
    {
        $entry = $I->grabEntryFixture('page-draft');
        $urlManager = Yii::$app->getUrlManager();

        $I->amOnPage($urlManager->createAbsoluteUrl($entry->getRoute()));
        $I->seeResponseCodeIs(404);

        $I->amOnDraftSubdomain();
        $I->amOnPage($urlManager->createAbsoluteUrl($entry->getRoute()));
        $I->seeResponseCodeIs(200);
        $I->haveHttpHeader('x-robots-tag', 'none');
    }

    public function checkDraftEntryWithoutDraftUrl(FunctionalTester $I): void
    {
    }

    public function checkDisabledEntry(FunctionalTester $I): void
    {
        $entry = $I->grabEntryFixture('page-disabled');
        $urlManager = Yii::$app->getUrlManager();

        $I->amOnPage($urlManager->createUrl($entry->getRoute()));
        $I->seeResponseCodeIs(404);
    }

    public function checkEntryWithDisabledRoute(FunctionalTester $I): void
    {
        $entry = $I->grabEntryFixture('post-2');
        $urlManager = Yii::$app->getUrlManager();

        $I->amOnPage($urlManager->createUrl($entry->getRoute()));
        $I->seeResponseCodeIs(404);
    }

    public function checkEntrySlugWithTrailingSlash(FunctionalTester $I): void
    {
        $entry = $I->grabEntryFixture('page-enabled');
        $urlManager = Yii::$app->getUrlManager();

        $I->amOnPage($urlManager->createUrl($entry->getRoute()) . '/');
        $I->canSeeCurrentUrlEquals($urlManager->createUrl($entry->getRoute()));
    }
}
