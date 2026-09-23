<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;

class EntrySubmenuTest extends TestCase
{
    use CmsFixtureTrait;

    /**
     * The first tab is the record itself, so it is named after the record rather than "General".
     */
    public function testTheUpdateItemIsNamedAfterTheEntryType(): void
    {
        $entry = TestEntry::findOne(1);

        self::assertSame('Page', $entry->getAdminType());
        self::assertStringContainsString('>Page</', EntrySubmenu::make()->model($entry)->render());
    }

    public function testASubentryLeadsBackToTheSubentriesOfItsParent(): void
    {
        $html = EntrySubmenu::make()->model(TestEntry::findOne(4))->render();

        self::assertStringContainsString('<a class="nav-link nav-back-link" href="/admin/cms/entry/index?type=', $html);
        self::assertStringContainsString('parent=1"', $html);
    }

    public function testARootEntryHasNoBackButton(): void
    {
        self::assertStringNotContainsString('nav-back-link', EntrySubmenu::make()->model(TestEntry::findOne(1))->render());
    }
}
