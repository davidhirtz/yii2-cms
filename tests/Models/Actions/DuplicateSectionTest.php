<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
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
}
