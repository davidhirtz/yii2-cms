<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids\Columns;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\MenuColumn;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

class MenuColumnTest extends TestCase
{
    private const int MAIN = 1;
    private const int FOOTER = 2;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Entry::getModule()->setMenus(fn (): array => [
            Menu::make(self::MAIN)->name('Main menu'),
            Menu::make(self::FOOTER)->name('Footer'),
        ]);
    }

    public function testAnEntryInNoMenuGetsNoIcon(): void
    {
        $content = (string)$this->createColumn()->renderBody(TestEntry::create(), 0, 0);

        self::assertStringNotContainsString('<i', $content);
    }

    /**
     * The tooltip names every menu the entry is in, since the icon cannot.
     */
    public function testTheIconNamesTheMenus(): void
    {
        $entry = TestEntry::create();
        $entry->menu_ids = [self::MAIN, self::FOOTER];

        $content = (string)$this->createColumn()->renderBody($entry, 0, 0);

        self::assertStringContainsString('fa-stream', $content);
        self::assertStringContainsString('title="Main menu, Footer"', $content);
    }

    /**
     * @return MenuColumn<Entry>
     */
    private function createColumn(): MenuColumn
    {
        return MenuColumn::make();
    }
}
