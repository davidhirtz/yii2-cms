<?php

namespace davidhirtz\yii2\cms\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use davidhirtz\yii2\cms\tests\support\fixtures\traits\CmsFixturesTrait;
use Yii;

class EntryTest extends Unit
{
    use CmsFixturesTrait;

    public function testCreateIndexEntry(): void
    {
        $entry = TestEntry::create();
        $entry->name = 'Home';
        $entry->slug = $entry::getModule()->entryIndexSlug;

        $this->assertTrue($entry->save());
        $this->assertTrue($entry->isIndex());
    }

    public function testCreateEntryValidationErrors()
    {
        $entry = TestEntry::create();
        $entry->setAttribute('type', 'invalid');

        /** @var Entry $existing */
        $existing = $this->tester->grabFixture('entries', 'page-1');
        $entry->slug = $existing->slug;

        $entry->save();

        $this->assertNotEmpty($entry->getErrors('name'));
        $this->assertNotEmpty($entry->getErrors('type'));
        $this->assertNotEmpty($entry->getErrors('slug'));
    }

    public function testCreateI18nEntry(): void
    {
        Yii::$app->language = 'de';

        $this->assertEquals(0, Entry::find()->count());

        $entry = Entry::create();
        $entry->name = 'Startseite';
        $entry->slug = $entry::getModule()->entryIndexSlug;

        $this->assertTrue($entry->save());
        $this->assertTrue($entry->isIndex());
    }

    public function testEntryAssets(): void
    {
        /** @var Entry $entry */
        $entry = $this->tester->grabFixture('entries', 'page-1');

        $this->assertEquals(2, $entry->asset_count);
        $this->assertEquals(4, count($entry->assets));
        $this->assertEquals(1, count($entry->getVisibleAssets()));

        $entry->populateAssetRelations();
        $this->assertEquals(2, count($entry->assets));
    }
}
