<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;

class ViaRelationOrderTest extends TestCase
{
    use CmsFixtureTrait;

    public function testASectionReadsItsLinkedEntriesInTheirOrder(): void
    {
        Entry::getModule()->enableSectionEntries = true;

        // Section 3 links entry 6 at position 1 and entry 1 at position 2.
        $section = Section::findOne(3);
        self::assertNotNull($section);

        self::assertSame([6, 1], array_map(fn (EntryRelation $relation): int => $relation->entry_id, $section->entryRelations));
        self::assertSame([6, 1], array_map(fn (Entry $entry): int => $entry->id, $section->getVisibleEntries()));
    }

    public function testACategoryReadsItsEntriesInTheirOrder(): void
    {
        EntryCategory::updateAll(['position' => 1], ['category_id' => 1, 'entry_id' => 2]);
        EntryCategory::updateAll(['position' => 2], ['category_id' => 1, 'entry_id' => 1]);

        $category = Category::findOne(1);
        self::assertNotNull($category);

        self::assertSame([2, 1], array_map(fn (Entry $entry): int => $entry->id, array_values($category->entries)));
    }

    public function testAnOrderOfTheRelationsOwnIsKept(): void
    {
        $category = Category::findOne(1);
        self::assertNotNull($category);

        $ids = $category->getEntries()->orderBy(['id' => SORT_DESC])->select('id')->column();

        self::assertSame([2, 1], array_map(intval(...), $ids));
    }
}
