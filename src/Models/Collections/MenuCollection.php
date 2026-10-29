<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Collections;

use BackedEnum;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Web\Request;
use Yii;

/**
 * Every autoloaded menu is loaded in one query per request, one declaring `autoload(false)` on its own.
 */
class MenuCollection
{
    use ModuleTrait;

    /**
     * @var array<int, Entry>|null
     */
    protected static ?array $entries = null;

    /**
     * @var array<int, array<int, Entry>>
     */
    protected static array $menuEntries = [];

    /**
     * @return array<int, Entry>
     */
    public static function getItems(int|BackedEnum $menu): array
    {
        $menuId = Menu::getValue($menu);

        if (!static::getModule()->findMenu($menuId)?->isAutoloaded()) {
            return static::$menuEntries[$menuId] ??= static::findEntries($menuId);
        }

        return array_filter(static::getEntries(), fn (Entry $entry) => $entry->isMenuItem($menuId));
    }

    /**
     * @return array<int, Entry>
     */
    public static function getRootItems(int|BackedEnum $menu): array
    {
        return array_filter(static::getItems($menu), fn (Entry $entry) => !$entry->parent_id);
    }

    /**
     * @return array<int, Entry>
     */
    public static function getSubmenuItems(Entry $parent, int|BackedEnum $menu): array
    {
        return !$parent->entry_count ? [] : array_filter(
            static::getItems($menu),
            fn (Entry $entry) => $entry->parent_id === $parent->id
        );
    }

    /**
     * @return array<int, Entry>
     */
    public static function getEntries(): array
    {
        return static::$entries ??= static::findEntries(...static::getAutoloadedMenuIds());
    }

    /**
     * @return list<int>
     */
    protected static function getAutoloadedMenuIds(): array
    {
        $menus = array_filter(static::getModule()->getMenus(), fn (Menu $menu) => $menu->isAutoloaded());
        return array_keys($menus);
    }

    /**
     * The entries are loaded once per request, so the cms `Bootstrap` clears them for the next one.
     */
    public static function reset(): void
    {
        static::$entries = null;
        static::$menuEntries = [];
    }

    /**
     * @return array<int, Entry>
     */
    protected static function findEntries(int ...$menuIds): array
    {
        if (!$menuIds) {
            return [];
        }

        Yii::debug('Loading menu items ...');

        return static::getEntryQuery()
            ->andWhereMenu(...$menuIds)
            ->all();
    }

    /**
     * @return EntryQuery<Entry>
     */
    protected static function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->withTranslations()
            ->withPermalinks()
            // A menu renders outside the cms controller too, which is what sets the status otherwise
            ->whereStatus(Request::current()?->getIsDraft() ? Entry::STATUS_DRAFT : Entry::STATUS_ENABLED)
            ->andWhereParentStatus()
            ->andWhereCurrentTenant()
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id');
    }
}
