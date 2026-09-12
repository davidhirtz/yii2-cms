<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetSubmenuItem;

/**
 * A file upload refreshes the asset count out of band, which only works while both submenus render it under the id
 * the upload names.
 */
class AssetsItemTest extends TestCase
{
    public function testTheEntrySubmenuRendersTheAssetsItemId(): void
    {
        $entry = Entry::create();
        $entry->id = 1;

        self::assertStringContainsString(
            'id="' . AssetSubmenuItem::ID . '"',
            (string)EntrySubmenu::make()->model($entry),
        );
    }

    public function testTheSectionSubmenuRendersTheAssetsItemId(): void
    {
        $entry = Entry::create();
        $entry->id = 1;

        $section = Section::create();
        $section->id = 1;
        $section->populateRelation('entry', $entry);

        self::assertStringContainsString(
            'id="' . AssetSubmenuItem::ID . '"',
            (string)SectionSubmenu::make()->model($section),
        );
    }
}
