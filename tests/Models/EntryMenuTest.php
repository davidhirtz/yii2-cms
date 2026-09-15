<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Test\TestCase;
use Override;

class EntryMenuTest extends TestCase
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

    public function testAnEntryIsInNoMenuByDefault(): void
    {
        $entry = $this->createEntry();

        self::assertNull($entry->menu_ids);
        self::assertSame([], $entry->getMenuIds());
        self::assertSame([], $entry->getMenus());
        self::assertFalse($entry->isMenuItem());
        self::assertFalse($entry->isMenuItem(self::MAIN));
    }

    /**
     * A form posts strings, and the column is a list of ints.
     */
    public function testThePostedValuesAreNormalizedToInts(): void
    {
        $entry = $this->createEntry();

        self::assertTrue($entry->load(['Entry' => ['menu_ids' => ['1', '2']]]));
        self::assertTrue($entry->validate(), print_r($entry->getErrors(), true));

        self::assertSame([self::MAIN, self::FOOTER], $entry->menu_ids);
        self::assertSame([self::MAIN, self::FOOTER], $entry->getMenuIds());
        self::assertTrue($entry->isMenuItem());
        self::assertTrue($entry->isMenuItem(self::FOOTER));
    }

    public function testNothingCheckedIsNull(): void
    {
        $entry = $this->createEntry();
        $entry->menu_ids = [self::MAIN];

        self::assertTrue($entry->load(['Entry' => ['menu_ids' => '']]));
        self::assertTrue($entry->validate(), print_r($entry->getErrors(), true));

        self::assertNull($entry->menu_ids);
    }

    public function testADuplicateIsDroppedAndTheOrderIsTheDeclaration(): void
    {
        $entry = $this->createEntry();
        $entry->menu_ids = [self::FOOTER, self::MAIN, self::FOOTER];

        self::assertTrue($entry->validate(), print_r($entry->getErrors(), true));

        self::assertSame([self::FOOTER, self::MAIN], $entry->menu_ids);
    }

    /**
     * The declaration is project configuration a record cannot answer for, so an id nothing declares is dropped
     * rather than reported — the entry is still saveable, it is simply in no such menu.
     */
    public function testAnUndeclaredMenuIsDropped(): void
    {
        $entry = $this->createEntry();
        $entry->menu_ids = [self::MAIN, 99];

        self::assertTrue($entry->validate(), print_r($entry->getErrors(), true));

        self::assertSame([self::MAIN], $entry->menu_ids);
    }

    public function testAnUnavailableMenuIsNotOffered(): void
    {
        Entry::getModule()->setMenus(fn (): array => [
            Menu::make(self::MAIN)->name('Main menu'),
            Menu::make(self::FOOTER)->name('Footer')->available(false),
        ]);

        $entry = $this->createEntry();
        $entry->menu_ids = [self::MAIN, self::FOOTER];

        self::assertSame([self::MAIN], array_keys($entry->getAvailableMenus()));
        self::assertTrue($entry->validate(), print_r($entry->getErrors(), true));

        self::assertSame([self::MAIN], $entry->menu_ids);
    }

    /**
     * A rule can stop matching records that are already in the menu, and locking those out of every later save —
     * or dropping them silently — is worse than the hole.
     */
    public function testAMenuTheEntryIsAlreadyInStaysValid(): void
    {
        $entry = $this->createEntry();
        $entry->menu_ids = [self::MAIN, self::FOOTER];

        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

        Entry::getModule()->setMenus(fn (): array => [
            Menu::make(self::MAIN)->name('Main menu'),
            Menu::make(self::FOOTER)->name('Footer')->available(false),
        ]);

        $entry = Entry::findOne($entry->id);
        self::assertNotNull($entry);

        self::assertSame([self::MAIN, self::FOOTER], array_keys($entry->getAvailableMenus()));
        self::assertTrue($entry->validate(), print_r($entry->getErrors(), true));

        self::assertSame([self::MAIN, self::FOOTER], $entry->menu_ids);
    }

    public function testTheMenusAreTheDeclaredObjects(): void
    {
        $entry = $this->createEntry();
        $entry->menu_ids = [self::FOOTER];

        $menus = $entry->getMenus();

        self::assertSame([self::FOOTER], array_keys($menus));
        self::assertSame('Footer', $menus[self::FOOTER]->getName());
    }

    /**
     * The value survives the round trip as a list of ints, not as JSON text.
     */
    public function testTheValueIsStoredAsAList(): void
    {
        $entry = $this->createEntry();
        $entry->menu_ids = [self::MAIN];

        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

        $entry = Entry::findOne($entry->id);
        self::assertNotNull($entry);

        self::assertSame([self::MAIN], $entry->menu_ids);
        self::assertSame([self::MAIN], $entry->getOldMenuIds());
    }

    /**
     * The fallback formatter `print_r()`s a list of ids, which is not a diff anyone can read.
     */
    public function testTheTrailNamesTheMenus(): void
    {
        $entry = $this->createEntry();

        self::assertSame('Main menu, Footer', $entry->formatTrailAttributeValue('menu_ids', [1, 2]));
        self::assertSame('', $entry->formatTrailAttributeValue('menu_ids', null));
        self::assertSame('Main menu', $entry->formatTrailAttributeValue('menu_ids', [1, 99]));
    }

    private function createEntry(): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->name = 'Entry';
        $entry->slug = 'entry';

        return $entry;
    }
}
