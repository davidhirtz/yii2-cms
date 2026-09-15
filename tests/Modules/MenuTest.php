<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules;

use Closure;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Module;
use Hirtz\Cms\Test\TestCase;
use yii\base\InvalidConfigException;

/**
 * A menu is a project-level catalogue rather than per-record state, so it is declared on the module beside the
 * feature flags, not on the model.
 */
class MenuTest extends TestCase
{
    public function testNoMenusAreDeclared(): void
    {
        self::assertSame([], $this->getModule()->getMenus());
        self::assertNull($this->getModule()->findMenu(1));
    }

    public function testTheModuleDeclaresTheMenus(): void
    {
        $this->setMenus(fn (): array => [
            Menu::make(1)
                ->name('Main menu')
                ->icon('bars'),
            Menu::make(2)
                ->name('Footer')
                ->autoload(false),
        ]);

        $menus = $this->getModule()->getMenus();

        self::assertSame([1, 2], array_keys($menus));
        self::assertSame('Main menu', $menus[1]->getName());
        self::assertSame('bars', $menus[1]->getIcon());

        self::assertTrue($menus[1]->isAutoloaded());
        self::assertFalse($menus[2]->isAutoloaded());

        self::assertSame($menus[1], $this->getModule()->findMenu(1));
        self::assertNull($this->getModule()->findMenu(3));
        self::assertNull($this->getModule()->findMenu(null));
    }

    /**
     * The declaration is resolved once, so a menu is the same object wherever it is read.
     */
    public function testTheMenusAreResolvedOnce(): void
    {
        $calls = 0;

        $this->setMenus(function () use (&$calls): array {
            $calls++;
            return [Menu::make(1)->name('Main menu')];
        });

        $this->getModule()->getMenus();
        $this->getModule()->getMenus();

        self::assertSame(1, $calls);
    }

    public function testAMenuIsAvailableAndAutoloadedByDefault(): void
    {
        $menu = Menu::make(1)->name('Main menu');

        self::assertTrue($menu->isAvailable($this->createEntry(Entry::TYPE_DEFAULT)));
        self::assertTrue($menu->isAutoloaded());
    }

    /**
     * A menu is scoped by the record it is offered for, never by the request — the admin edits entries of any
     * tenant, so the request's tenant is not the entry's.
     */
    public function testAvailabilityIsDecidedPerEntry(): void
    {
        $menu = Menu::make(1)
            ->name('Main menu')
            ->available(fn (Entry $entry): bool => $entry->type === 2);

        self::assertTrue($menu->isAvailable($this->createEntry(2)));
        self::assertFalse($menu->isAvailable($this->createEntry(Entry::TYPE_DEFAULT)));
    }

    public function testAvailabilityTakesAPlainBool(): void
    {
        $entry = $this->createEntry(Entry::TYPE_DEFAULT);

        self::assertFalse(Menu::make(1)->available(false)->isAvailable($entry));
        self::assertTrue(Menu::make(1)->available()->isAvailable($entry));
    }

    public function testAMenuWithoutANameIsRefused(): void
    {
        $this->setMenus(fn (): array => [Menu::make(1)]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('has no name');

        $this->getModule()->getMenus();
    }

    public function testTwoMenusCannotShareAValue(): void
    {
        $this->setMenus(fn (): array => [
            Menu::make(1)->name('First'),
            Menu::make(1)->name('Second'),
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('declares "1" twice');

        $this->getModule()->getMenus();
    }

    public function testAnythingButAMenuIsRefused(): void
    {
        $this->setMenus(fn (): array => ['Main menu']);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('must be a list of');

        $this->getModule()->getMenus();
    }

    /**
     * @param Closure(): array<mixed> $menus
     */
    private function setMenus(Closure $menus): void
    {
        $this->getModule()->setMenus($menus);
    }

    private function createEntry(int $type): Entry
    {
        $entry = Entry::create();
        $entry->type = $type;

        return $entry;
    }

    private function getModule(): Module
    {
        return Entry::getModule();
    }
}
