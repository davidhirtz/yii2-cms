<?php

namespace davidhirtz\yii2\cms\widgets;

use app\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use Yii;

class NavItems
{
    private static ?array $_entries = null;

    /**
     * @return array<int, Entry>
     */
    public static function getMenuItems(): array
    {
        return array_filter(static::getEntries(), fn($entry) => $entry->show_in_menu);
    }

    /**
     * @return array<int, Entry>
     */
    public static function getMainMenuItems(): array
    {
        return array_filter(static::getEntries(), fn($entry) => $entry->show_in_menu && !$entry->parent_id);
    }

    /**
     * @return array<int, Entry>
     */
    public static function getSubmenuItems(Entry $parent): array
    {
        return !$parent->entry_count ? [] : array_filter(
            static::getMenuItems(),
            fn(Entry $entry) => $entry->parent_id == $parent->id
        );
    }

    /**
     * @return array<int, Entry>
     */
    public static function getFooterItems(): array
    {
        return array_filter(static::getEntries(), fn($entry) => $entry->show_in_footer);
    }

    /**
     * @return array<int, Entry>
     */
    protected static function getEntries(): array
    {
        if (static::$_entries === null) {
            Yii::debug('Loading menu items ...');

            static::$_entries = static::getEntryQuery()->all();
        }

        return static::$_entries;
    }

    protected static function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->addSelect(Entry::instance()->getI18nAttributesNames(['slug', 'parent_slug']))
            ->where(static::getEntryQueryWhere())
            ->whereStatus()
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
}
