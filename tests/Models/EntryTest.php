<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Fixtures\Traits\FixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;

class EntryTest extends TestCase
{
    use FixtureTrait;
    use ModuleTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::getModule()->enableNestedEntries = true;
        self::getModule()->enableSectionEntries = true;
    }

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

        $existing = $this->getEntryFromFixture('page-enabled');
        $entry->slug = $existing->slug;

        $entry->save();

        self::assertNotEmpty($entry->getErrors('name'));
        self::assertNotEmpty($entry->getErrors('type'));
        self::assertNotEmpty($entry->getErrors('slug'));
    }

    public function testUpdateEntry(): void
    {
        $entry = $this->getEntryFromFixture('post-1');
        $entry->parent_id = 2;

        self::assertTrue($entry->update() === 1);

        self::assertEquals('test-2', $entry->parent_slug);
        self::assertEquals('2', $entry->path);
        self::assertEquals(2, $entry->parent->id);
        self::assertEquals(2, $entry->parent->entry_count);

        $previous = $this->getEntryFromFixture('page-enabled');
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
        $entry = $this->getEntryFromFixture('page-enabled');
        $post = $this->getEntryFromFixture('post-1');

        self::assertTrue(!!$entry->delete());
        self::assertNull(TestEntry::findOne($entry->id));
        self::assertNull(TestEntry::findOne($post->id));
    }

    public function testEntryAssets(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        self::assertCount(6, $entry->assets);
        self::assertCount(1, $entry->getVisibleAssets());

        $entry->populateAssetRelations();
        self::assertCount(2, $entry->assets);
    }
}
