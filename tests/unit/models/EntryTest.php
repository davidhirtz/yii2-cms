<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use davidhirtz\yii2\cms\tests\support\fixtures\traits\CmsFixturesTrait;
use davidhirtz\yii2\cms\tests\support\UnitTester;
use Yii;

class EntryTest extends Unit
{
    use CmsFixturesTrait;

    protected UnitTester $tester;

    public function testCreateIndexEntry(): void
    {
        $entry = TestEntry::create();
        $entry->name = 'Home';
        $entry->slug = $entry::getModule()->entryIndexSlug;

        self::assertTrue($entry->save());
        self::assertTrue($entry->isIndex());
    }

    public function testCreateEntryValidationErrors(): void
    {
        $entry = TestEntry::create();
        $entry->setAttribute('type', 'invalid');

        $existing = $this->tester->grabEntryFixture('page-enabled');
        $entry->slug = $existing->slug;

        $entry->save();

        self::assertNotEmpty($entry->getErrors('name'));
        self::assertNotEmpty($entry->getErrors('type'));
        self::assertNotEmpty($entry->getErrors('slug'));
    }

    public function testCreateI18nEntry(): void
    {
        Yii::$app->language = 'de';

        self::assertEquals(0, TestEntry::find()->count());

        $entry = TestEntry::create();
        $entry->name = 'Startseite';
        $entry->slug = $entry::getModule()->entryIndexSlug;

        self::assertTrue($entry->save());
        self::assertTrue($entry->isIndex());
    }

    public function testUpdateEntry(): void
    {
        $entry = $this->tester->grabEntryFixture('post-1');
        $entry->parent_id = 2;

        self::assertTrue(!!$entry->update());

        self::assertEquals('test-2', $entry->parent_slug);
        self::assertEquals('2', $entry->path);
        self::assertEquals(2, $entry->parent->id);
        self::assertEquals(2, $entry->parent->entry_count);

        $previous = $this->tester->grabEntryFixture('page-enabled');
        self::assertEquals(1, $previous->entry_count);

        $parent = $entry->parent;

        $parent->status = TestEntry::STATUS_ENABLED;
        $parent->slug = 'new-slug';

        self::assertTrue(!!$parent->update());

        $entry->refresh();

        self::assertEquals('new-slug', $entry->parent_slug);
        self::assertEquals(TestEntry::STATUS_ENABLED, $entry->parent_status);
    }

    public function testDeleteEntry(): void
    {
        $entry = $this->tester->grabEntryFixture('page-enabled');
        $post = $this->tester->grabEntryFixture('post-1');

        self::assertTrue(!!$entry->delete());
        self::assertNull(TestEntry::findOne($entry->id));
        self::assertNull(TestEntry::findOne($post->id));
    }

    public function testEntryAssets(): void
    {
        $entry = $this->tester->grabEntryFixture('page-enabled');

        self::assertEquals(6, count($entry->assets));
        self::assertEquals(1, count($entry->getVisibleAssets()));

        $entry->populateAssetRelations();
        self::assertEquals(2, count($entry->assets));
    }
}
