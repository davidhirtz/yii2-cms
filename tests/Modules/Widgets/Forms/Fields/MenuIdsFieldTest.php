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
     * A form reload renders the loaded record without validating it, so a menu guarded on the posted type was
     * offered only once the entry had been saved.
     */
    public function testAMenuGuardedByTheTypeFollowsAPostedType(): void
    {
        Entry::getModule()->setMenus(fn (): array => [
            Menu::make(self::MAIN)->name('Main menu'),
            Menu::make(self::FOOTER)
                ->name('Footer')
                ->available(fn (Entry $entry): bool => $entry->type === TestEntry::TYPE_POST),
        ]);

        $entry = TestEntry::create();
        $entry->type = TestEntry::TYPE_PAGE;

        self::assertStringNotContainsString('value="2"', $this->render($entry));

        self::assertTrue($entry->load(['Entry' => ['type' => (string)TestEntry::TYPE_POST]]));
        self::assertSame(TestEntry::TYPE_POST, $entry->type);

        self::assertStringContainsString('value="2"', $this->render($entry));
    }

    /**
     * The field decides its visibility on the items its own `configure()` resolved, which only holds while
     * `Widgets\Forms\Fieldset` renders it before asking — rendering it standalone would not notice a regression.
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
            '<label class="text-invalid label" title="Parent entry &quot;Parent&quot; is not in this menu" data-tooltip="" for="entry-menu-ids-1">',
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
