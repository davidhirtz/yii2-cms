<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;

class DuplicateSectionTest extends TestCase
{
    use CmsFixtureTrait;

    /**
     * The children are inserted with validation and may be refused: the counts follow what was inserted, not what
     * the original says.
     */
    public function testTheCountsFollowTheChildrenActuallyCopied(): void
    {
        $entry = TestEntry::create();
        $entry->loadDefaultValues();
        $entry->status = TestEntry::STATUS_ENABLED;
        $entry->type = TestEntry::TYPE_PAGE;
        $entry->name = 'Original';
        $entry->slug = 'original';

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        $section = Section::create();
        $section->loadDefaultValues();
        $section->status = Section::STATUS_ENABLED;
        $section->type = Section::TYPE_DEFAULT;
        $section->name = 'Section';
        $section->populateEntryRelation($entry);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        // counts the copy cannot reproduce: there is nothing behind them
        $section->updateAttributes(['asset_count' => 2, 'entry_count' => 3]);

        $duplicate = DuplicateSection::create(['section' => $section]);
        $stored = Section::findOne($duplicate->id);

        self::assertNotNull($stored);
        self::assertSame(0, $stored->asset_count);
        self::assertSame(0, $stored->entry_count);
    }

    public function testTheLinkedEntriesAreCopiedInTheirOrder(): void
    {
        $duplicate = DuplicateSection::create(['section' => TestSection::findOne(3)]);

        $entryIds = SectionEntry::find()
            ->select('entry_id')
            ->where(['model_id' => $duplicate->id])
            ->orderBy(['position' => SORT_ASC])
            ->column();

        self::assertSame([6, 1], array_map(intval(...), $entryIds));
    }

    public function testTheAssetsAreCopiedInTheirOrder(): void
    {
        foreach ([4 => 4, 5 => 3, 6 => 2, 7 => 1] as $id => $position) {
            SectionAsset::updateAll(['position' => $position], ['id' => $id]);
        }

        $duplicate = DuplicateSection::create(['section' => TestSection::findOne(1)]);

        $fileIds = SectionAsset::find()
            ->select('file_id')
            ->where(['model_id' => $duplicate->id])
            ->orderBy(['position' => SORT_ASC])
            ->column();

        self::assertSame([6, 5, 4, 3], array_map(intval(...), $fileIds));
    }
}
