<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\MenuIdsField;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;

class MenuIdsFieldTest extends TestCase
{
    private const int MAIN = 1;
    private const int FOOTER = 2;

    /**
     * A project that declares no menus gets no field at all.
     */
    public function testTheFieldIsHiddenWithoutMenus(): void
    {
        self::assertSame('', $this->render(TestEntry::create()));
    }

    public function testACheckboxPerMenu(): void
    {
        $this->setMenus();

        $entry = TestEntry::create();
        $entry->menu_ids = [self::FOOTER];

        $content = $this->render($entry);

        self::assertStringContainsString('<input type="hidden" name="Entry[menu_ids]" value="">', $content);
        self::assertStringContainsString('<input type="checkbox" id="entry-menu-ids-1" class="input" name="Entry[menu_ids][]" value="1">', $content);
        self::assertStringContainsString('<input type="checkbox" id="entry-menu-ids-2" class="input" name="Entry[menu_ids][]" value="2" checked>', $content);
        self::assertStringContainsString('<label class="label" for="entry-menu-ids-1">Main menu</label>', $content);
    }

    public function testAnUnavailableMenuIsNotOffered(): void
    {
        Entry::getModule()->setMenus(fn (): array => [
            Menu::make(self::MAIN)->name('Main menu'),
            Menu::make(self::FOOTER)->name('Footer')->available(false),
        ]);

        $content = $this->render(TestEntry::create());

        self::assertStringContainsString('value="1"', $content);
        self::assertStringNotContainsString('value="2"', $content);
    }

    /**
     * `Widgets\Forms\Fieldset` asks a field whether it is visible before the field configures itself, so a
     * field that decides on what its own `configure()` resolved is dropped from the form without a trace —
     * rendering it standalone would never notice.
     */
    public function testTheFieldReachesTheEntryForm(): void
    {
        $this->setMenus();

        $content = EntryActiveForm::make()
            ->model(TestEntry::create())
            ->render();

        self::assertStringContainsString('name="Entry[menu_ids][]" value="1"', $content);
        self::assertStringContainsString('name="Entry[menu_ids][]" value="2"', $content);
    }

    public function testTheEntryFormRendersNoFieldWithoutMenus(): void
    {
        $content = EntryActiveForm::make()
            ->model(TestEntry::create())
            ->render();

        self::assertStringNotContainsString('menu_ids', $content);
    }

    /**
     * A menu item whose ancestor is not in the same menu is never reached through it, which is invisible in a
     * checkbox on its own.
     */
    public function testAnAncestorOutsideTheMenuIsMarked(): void
    {
        $this->setMenus();
        Entry::getModule()->enableNestedEntries = true;

        $parent = $this->createEntry('Parent', 'parent', [self::FOOTER]);
        $child = $this->createEntry('Child', 'child', [self::MAIN], $parent);

        $content = $this->render($child);

        self::assertStringContainsString(
            '<div class="text-invalid form-checkbox" title="Parent entry &quot;Parent&quot; is not in this menu" data-tooltip="">',
            $content
        );

        // the menu the parent is in is not marked
        self::assertSame(1, substr_count($content, 'text-invalid'));
    }

    /**
     * @param list<int> $menuIds
     */
    private function createEntry(string $name, string $slug, array $menuIds, ?Entry $parent = null): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->menu_ids = $menuIds;
        $entry->populateParentRelation($parent);

        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

        return $entry;
    }

    private function setMenus(): void
    {
        Entry::getModule()->setMenus(fn (): array => [
            Menu::make(self::MAIN)->name('Main menu'),
            Menu::make(self::FOOTER)->name('Footer'),
        ]);
    }

    private function render(Entry $entry): string
    {
        return MenuIdsField::make()
            ->model($entry)
            ->render();
    }
}
