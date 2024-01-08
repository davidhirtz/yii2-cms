<?php

/**
 * @noinspection PhpUnused
 */

namespace davidhirtz\yii2\cms\tests\functional;

use davidhirtz\yii2\cms\controllers\SiteController;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\tests\support\FunctionalTester;
use davidhirtz\yii2\cms\widgets\Sections;
use Yii;

class SiteControllerCest
{
    public function _before(FunctionalTester $I): void
    {
        Yii::$container->setDefinitions([
            SiteController::class => [
                'layout' => '@tests/data/views/layouts/main',
            ],
            Sections::class => [
                'viewFile' => '@tests/data/views/site/_sections',
            ],
        ]);
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

    public function checkEntries(FunctionalTester $I): void
    {
        $entry = Entry::create();
        $entry->name = 'Test';
        $entry->insert();

        $I->amOnPage('/test');
        $I->seeResponseCodeIs(404);

        $section = Section::create();
        $section->name = 'Test';
        $section->populateEntryRelation($entry);
        $section->insert();

        $I->amOnPage('/test');
        $I->canSeeInTitle($entry->name);

        $I->amOnPage('/test/');
        $I->canSeeInTitle($entry->name);

        $subentry = Entry::create();
        $subentry->name = 'Subtest';
        $subentry->populateParentRelation($entry);
        $subentry->insert();
    }
}
