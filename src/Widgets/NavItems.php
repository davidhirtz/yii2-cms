<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Yii;

/**
 * @template T of Entry
 */
class NavItems
{
    /**
     * @var array<int, T>|null
     */
    protected static ?array $entries = null;

    /**
     * @return array<int, T>
     */
    public static function getMenuItems(): array
    {
        return array_filter(static::getEntries(), static::getIsMenuItem(...));
    }

    /**
     * @return array<int, T>
     */
    public static function getMainMenuItems(): array
    {
        return array_filter(static::getEntries(), fn ($entry) => static::getIsMenuItem($entry) && !$entry->parent_id);
    }

    /**
     * @return array<int, T>
     */
    public static function getSubmenuItems(Entry $parent): array
    {
        return !$parent->entry_count ? [] : array_filter(
            static::getMenuItems(),
            fn (Entry $entry) => $entry->parent_id === $parent->id
        );
    }

    /**
     * @return array<int, T>
     */
    public static function getFooterItems(): array
    {
        return array_filter(static::getEntries(), static::getIsFooterItem(...));
    }

    /**
     * @return array<int, T>
     */
    public static function getEntries(): array
    {
        static::$entries ??= static::findEntries();
        return static::$entries;
    }

    /**
     * The entries are loaded once per request, so the cms `Bootstrap` clears them for the next one.
     */
    public static function reset(): void
    {
        static::$entries = null;
    }

    /**
     * @return array<int, T>
     */
    protected static function findEntries(): array
    {
        Yii::debug('Loading menu items ...');
        return static::getEntryQuery()->all();
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
            ->where(static::getEntryQueryWhere())
            ->whereStatus()
            ->andWhereParentStatus()
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id');
    }

    /**
     * An `Entry` that declares neither attribute has no condition to add — the filters below answer `false` for
     * every record anyway, and `$where[0]` on an empty array was a `TypeError` on every page with a menu.
     *
     * @return array<string, mixed>
     */
    protected static function getEntryQueryWhere(): array
    {
        $where = [];
        $attributes = Entry::instance()->attributes();

        foreach (['show_in_menu', 'show_in_footer'] as $attribute) {
            if (in_array($attribute, $attributes, true)) {
                $where[] = [$attribute => 1];
            }
        }

        return match (count($where)) {
            0 => [],
            1 => $where[0],
            default => ['or', ...$where],
        };
    }

    /**
     * @param T $entry
     * @see \Hirtz\Cms\Models\Traits\MenuAttributeTrait::isMenuItem()
     */
    public static function getIsMenuItem(Entry $entry): bool
    {
        return (!method_exists($entry, 'hasShowInMenuEnabled') || $entry->hasShowInMenuEnabled())
            && method_exists($entry, 'isMenuItem')
            && $entry->isMenuItem();
    }

    /**
     * @param T $entry
     * @see \Hirtz\Cms\Models\Traits\FooterAttributeTrait::isFooterItem()
     */
    public static function getIsFooterItem(Entry $entry): bool
    {
        return (!method_exists($entry, 'hasShowInFooterEnabled') || $entry->hasShowInFooterEnabled())
            && method_exists($entry, 'isFooterItem')
            && $entry->isFooterItem();
    }
}
