<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Collections;

use Hirtz\Cms\Models\Collections\MenuCollection;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\ActiveQuery;
use Override;

class MenuCollectionTest extends TestCase
{
    private const int MAIN = 1;
    private const int FOOTER = 2;
    private const int SITEMAP = 3;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Entry::getModule()->enableNestedEntries = true;

        // a request scopes its queries by status, and the menu query inherits it
        EntryQuery::setStatus(Entry::STATUS_ENABLED);
    }

    #[Override]
    protected function tearDown(): void
    {
        MenuCollection::reset();
        ActiveQuery::resetStatus();

        parent::tearDown();
    }

    /**
     * A project that declares no menus has no menu query to run at all.
     */
    public function testAnEntryWithoutMenusHasNoMenu(): void
    {
        $this->createEntry('First', 'first');

        self::assertSame([], MenuCollection::getEntries());
        self::assertSame([], MenuCollection::getItems(self::MAIN));
    }

    public function testOnlyTheEntriesThatAskForItAreMenuItems(): void
    {
        $this->setMenus();

        $shown = $this->createEntry('Shown', 'shown', menuIds: [self::MAIN]);
        $this->createEntry('Hidden', 'hidden');

        self::assertSame([$shown->id], array_keys(MenuCollection::getItems(self::MAIN)));
    }

    public function testAnEntryCanBeInSeveralMenus(): void
    {
        $this->setMenus();

        $both = $this->createEntry('Both', 'both', menuIds: [self::MAIN, self::FOOTER]);

        self::assertSame([$both->id], array_keys(MenuCollection::getItems(self::MAIN)));
        self::assertSame([$both->id], array_keys(MenuCollection::getItems(self::FOOTER)));
    }

    public function testTheRootItemsAreTheTopLevelOnly(): void
    {
        $this->setMenus();

        $parent = $this->createEntry('Parent', 'parent', menuIds: [self::MAIN]);
        $child = $this->createEntry('Child', 'child', $parent, [self::MAIN]);

        self::assertSame([$parent->id], array_keys(MenuCollection::getRootItems(self::MAIN)));
        self::assertSame([$parent->id, $child->id], array_keys(MenuCollection::getItems(self::MAIN)));
    }

    public function testTheSubmenuIsTheChildrenOfItsParent(): void
    {
        $this->setMenus();

        $parent = $this->createEntry('Parent', 'parent', menuIds: [self::MAIN]);
        $child = $this->createEntry('Child', 'child', $parent, [self::MAIN]);

        $parent->refresh();

        self::assertSame([$child->id], array_keys(MenuCollection::getSubmenuItems($parent, self::MAIN)));

        // an entry with no children is not asked about at all
        self::assertSame([], MenuCollection::getSubmenuItems($child, self::MAIN));
    }

    public function testEachMenuHasItsOwnItems(): void
    {
        $this->setMenus();

        $footer = $this->createEntry('Footer', 'footer', menuIds: [self::FOOTER]);
        $this->createEntry('Menu', 'menu', menuIds: [self::MAIN]);

        self::assertSame([$footer->id], array_keys(MenuCollection::getItems(self::FOOTER)));
    }

    public function testAnUnpublishedEntryIsNotInTheMenu(): void
    {
        $this->setMenus();

        $this->createEntry('Draft', 'draft', menuIds: [self::MAIN], attributes: [
            'status' => Entry::STATUS_DISABLED,
        ]);

        self::assertSame([], MenuCollection::getItems(self::MAIN));
    }

    /**
     * The autoloaded menus are loaded once per request, whatever asks for them afterwards.
     */
    public function testTheEntriesAreLoadedOnce(): void
    {
        $this->setMenus();
        $this->createEntry('Shown', 'shown', menuIds: [self::MAIN, self::FOOTER]);

        MenuCollection::getItems(self::MAIN);

        $queries = $this->countQueries(function (): void {
            MenuCollection::getItems(self::MAIN);
            MenuCollection::getRootItems(self::MAIN);
            MenuCollection::getItems(self::FOOTER);
        });

        self::assertSame(0, $queries);
    }

    /**
     * A menu that does not autoload is kept out of the shared query and pays for one of its own, once.
     */
    public function testAMenuThatDoesNotAutoloadIsLoadedOnDemand(): void
    {
        $this->setMenus();

        $main = $this->createEntry('Main', 'main', menuIds: [self::MAIN]);
        $sitemap = $this->createEntry('Sitemap', 'sitemap', menuIds: [self::SITEMAP]);

        self::assertSame([$main->id], array_keys(MenuCollection::getEntries()));

        // the records and their eager loaded permalinks
        $queries = $this->countQueries(function () use ($sitemap): void {
            self::assertSame([$sitemap->id], array_keys(MenuCollection::getItems(self::SITEMAP)));
        });

        self::assertSame(2, $queries);
        self::assertSame(0, $this->countQueries(fn () => MenuCollection::getItems(self::SITEMAP)));
    }

    /**
     * An undeclared menu is nothing the collection can answer for, and must not fall through to the shared set.
     */
    public function testAnUndeclaredMenuIsEmpty(): void
    {
        $this->setMenus();
        $this->createEntry('Shown', 'shown', menuIds: [self::MAIN]);

        self::assertSame([], MenuCollection::getItems(99));
    }

    /**
     * The records a request loaded must never reach the next one, so `Bootstrap` drops them — a reset that only
     * ran in the tests would leave a resident application serving them forever.
     */
    public function testTheEntriesDoNotOutliveTheApplication(): void
    {
        $this->setMenus();
        $this->createEntry('Shown', 'shown', menuIds: [self::MAIN]);

        self::assertCount(1, MenuCollection::getItems(self::MAIN));

        $this->reloadApplication();
        $this->setMenus();

        $this->createEntry('Second', 'second', menuIds: [self::MAIN]);

        self::assertCount(2, MenuCollection::getItems(self::MAIN));
    }

    private function setMenus(): void
    {
        Entry::getModule()->setMenus(fn (): array => [
            Menu::make(self::MAIN)->name('Main menu'),
            Menu::make(self::FOOTER)->name('Footer'),
            Menu::make(self::SITEMAP)->name('Sitemap')->autoload(false),
        ]);

        EntryQuery::setStatus(Entry::STATUS_ENABLED);
    }

    /**
     * @param list<int> $menuIds
     * @param array<string, mixed> $attributes
     */
    private function createEntry(
        string $name,
        string $slug,
        ?Entry $parent = null,
        array $menuIds = [],
        array $attributes = []
    ): Entry {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->menu_ids = $menuIds ?: null;
        $entry->populateParentRelation($parent);
        $entry->setAttributes($attributes, false);

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }
}
