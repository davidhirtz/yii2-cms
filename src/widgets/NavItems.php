<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use Yii;

/**
 * @template T of Entry
 */
class NavItems
{
    protected static ?array $_entries = null;

    /**
     * @return array<int, T>
     */
    public static function getMenuItems(): array
    {
        return array_filter(static::getEntries(), fn ($entry) => static::getIsMenuItem($entry));
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
            fn (Entry $entry) => $entry->parent_id == $parent->id
        );
    }

    /**
     * @return array<int, T>
     */
    public static function getFooterItems(): array
    {
        return array_filter(static::getEntries(), fn ($entry) => static::getIsFooterItem($entry));
    }

    /**
     * @return array<int, T>
     */
    public static function getEntries(): array
    {
        static::$_entries ??= static::findEntries();
        return static::$_entries;
    }

    /**
     * @return array<int, T>
     */
    protected static function findEntries(): array
    {
        Yii::debug('Loading menu items ...');
        return static::getEntryQuery()->all();
    }

    protected static function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->addSelect(Entry::instance()->getI18nAttributesNames(['slug', 'parent_slug']))
            ->where(static::getEntryQueryWhere())
            ->whereStatus()
            ->andWhereParentStatus()
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id');
    }

    protected static function getEntryQueryWhere(): array
    {
        $where = [];
        $attributes = Entry::instance()->attributes();

        foreach (['show_in_menu', 'show_in_footer'] as $attribute) {
            if (in_array($attribute, $attributes)) {
                $where[] = [$attribute => 1];
            }
        }

        return count($where) > 1 ? ['or', ...$where] : $where[0];
    }

    /**
     * @param T $entry
     * @see \davidhirtz\yii2\cms\models\traits\MenuAttributeTrait::isMenuItem()
     */
    public static function getIsMenuItem(Entry $entry): bool
    {
        return (!method_exists($entry, 'hasShowInMenuEnabled') || $entry->hasShowInMenuEnabled())
            && method_exists($entry, 'isMenuItem')
            && $entry->isMenuItem();
    }

    /**
     * @param T $entry
     * @see \davidhirtz\yii2\cms\models\traits\FooterAttributeTrait::isFooterItem()
     */
    public static function getIsFooterItem(Entry $entry): bool
    {
        return (!method_exists($entry, 'hasShowInFooterEnabled') || $entry->hasShowInFooterEnabled())
            && method_exists($entry, 'isFooterItem')
            && $entry->isFooterItem();
    }
}
