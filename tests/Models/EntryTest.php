<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;

class EntryTest extends TestCase
{
    use CmsFixtureTrait;
    use ModuleTrait;

    #[\Override]
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
        $entry->slug = $entry::getModule()->entryIndexSlug ?: null;

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

        self::assertNotFalse($entry->update());

        self::assertEquals('test-2/post-1', $entry->getFormattedSlug());
        self::assertEquals([2], $entry->path);
        self::assertEquals(2, $entry->parent->id);
        self::assertEquals(2, $entry->parent->entry_count);

        $previous = $this->getEntryFromFixture('page-enabled');
        self::assertEquals(1, $previous->entry_count);

        $parent = $entry->parent;

        $parent->status = TestEntry::STATUS_ENABLED;
        $parent->slug = 'new-slug';

        self::assertTrue(!!$parent->update());

        $entry->refresh();

        self::assertEquals('new-slug/post-1', $entry->getFormattedSlug());
        self::assertEquals('new-slug/post-1', $entry->getPermalink()->uri);
        self::assertEquals(TestEntry::STATUS_ENABLED, $entry->parent_status);
    }

    /**
     * The counters are bookkeeping on the parent, so a parent that no longer validates — a custom attribute gone
     * stale, a rule tightened since it was saved — still has its children counted.
     */
    public function testTheCountersOfAParentThatDoesNotValidateAreUpdated(): void
    {
        $parent = $this->getEntryFromFixture('page-enabled');
        $parent->updateAttributes(['name' => '']);

        self::assertFalse($parent->validate());

        $sectionCount = $parent->section_count;
        $entryCount = $parent->entry_count;

        $section = Section::instantiateByType(Section::TYPE_DEFAULT);
        $section->loadDefaultValues();
        $section->populateEntryRelation($parent);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $child = TestEntry::create();
        $child->loadDefaultValues();
        $child->name = 'Child';
        $child->slug = 'child';
        $child->populateParentRelation($parent);

        self::assertTrue($child->insert(), print_r($child->getErrors(), true));

        $parent = TestEntry::findOne($parent->id);

        self::assertSame($sectionCount + 1, $parent->section_count);
        self::assertSame($entryCount + 1, $parent->entry_count);
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

        // The section assets belong to their sections now, not to the entry.
        self::assertCount(2, $entry->assets);
        self::assertCount(1, $entry->getVisibleAssets());
    }
}
