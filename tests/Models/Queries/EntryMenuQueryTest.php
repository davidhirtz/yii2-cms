<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Queries;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Test\TestCase;
use Override;

class EntryMenuQueryTest extends TestCase
{
    private const int MAIN = 1;
    private const int FOOTER = 2;
    private const int SITEMAP = 3;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Entry::getModule()->setMenus(fn (): array => [
            Menu::make(self::MAIN)->name('Main menu'),
            Menu::make(self::FOOTER)->name('Footer'),
            Menu::make(self::SITEMAP)->name('Sitemap'),
        ]);
    }

    /**
     * Asking for every declared menu is the case a layout pays for, and `IS NOT NULL` is what a database can
     * answer without a JSON call per row.
     */
    public function testEveryMenuIsTheCheapCondition(): void
    {
        $sql = Entry::find()
            ->andWhereMenu(self::MAIN, self::FOOTER, self::SITEMAP)
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString('NOT (`entry`.`menu_ids` IS NULL)', $sql);
        self::assertStringNotContainsString('JSON_CONTAINS', $sql);
    }

    public function testASubsetIsComparedPerMenu(): void
    {
        $sql = Entry::find()
            ->andWhereMenu(self::MAIN, self::FOOTER)
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString("JSON_CONTAINS(`entry`.`menu_ids`, '1')", $sql);
        self::assertStringContainsString("JSON_CONTAINS(`entry`.`menu_ids`, '2')", $sql);
    }

    public function testNoMenuMatchesNothing(): void
    {
        $sql = Entry::find()
            ->andWhereMenu()
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString('0=1', $sql);
    }

    public function testTheConditionFindsTheEntriesOfOneMenu(): void
    {
        $main = $this->createEntry('Main', 'main', [self::MAIN]);
        $this->createEntry('Footer', 'footer', [self::FOOTER]);
        $this->createEntry('None', 'none', []);

        $ids = Entry::find()
            ->andWhereMenu(self::MAIN)
            ->select(['id'])
            ->column();

        self::assertSame([$main->id], array_map(intval(...), $ids));
    }

    /**
     * @param list<int> $menuIds
     */
    private function createEntry(string $name, string $slug, array $menuIds): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->menu_ids = $menuIds ?: null;

        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

        return $entry;
    }
}
