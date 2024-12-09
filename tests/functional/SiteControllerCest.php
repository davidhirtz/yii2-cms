<?php

/**
 * @noinspection PhpUnused
 */

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

    public function checkEnabledEntry(FunctionalTester $tester): void
    {
        /** @var Entry $entry */
        $entry = $tester->grabFixture('entries', 'page-enabled');
        $urlManager = Yii::$app->getUrlManager();

        $tester->amOnPage($urlManager->createUrl($entry->getRoute()));

        $tester->canSeeResponseCodeIs(200);
        $tester->canSeeInTitle($entry->name);

        foreach ($entry->sections as $section) {
            if ($section->isEnabled()) {
                foreach ($section->getVisibleAssets() as $asset) {
                    if ($asset->isEnabled()) {
                        $tester->canSeeInSource($asset->file->getUrl());
                    } else {
                        $tester->cantSeeInSource($asset->file->getUrl());
                    }
                }
                $tester->canSeeInSource($section->content);
            } else {
                $tester->cantSeeInSource($section->content);
            }
        }

        /** @var Asset $asset */
        $asset = current(array_filter($entry->assets, fn (Asset $asset) => $asset->type == Asset::TYPE_META_IMAGE));
        $url = $urlManager->createAbsoluteUrl($asset->file->getUrl());

        $tester->canSeeInSource('<link href="' . $url . '" rel="image_src">');
    }

    public function checkDraftEntry(FunctionalTester $tester): void
    {
        /** @var Entry $entry */
        $entry = $tester->grabFixture('entries', 'page-draft');
        $urlManager = Yii::$app->getUrlManager();

        $tester->amOnPage($urlManager->createUrl($entry->getRoute()));
        $tester->canSeeResponseCodeIs(404);
    }
}
