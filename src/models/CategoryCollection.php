<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use Yii;
use yii\caching\TagDependency;

class CategoryCollection
{
    use ModuleTrait;

    public const CACHE_KEY = 'category-collection';

    private static ?array $_categories = null;

    /**
     * @return array<int, Category>
     */
    public static function getAll(bool $refresh = false): array
    {
        if (null === static::$_categories || $refresh) {
            $dependency = new TagDependency(['tags' => static::CACHE_KEY]);
            $duration = static::getModule()->categoryCachedQueryDuration;

            static::$_categories = $duration !== false
                ? Yii::$app->getDb()->cache(static::findAll(...), $duration, $dependency)
                : static::findAll();
        }

        return static::$_categories;
    }

    /**
     * @return array<int, Category>
     * @noinspection PhpUnused
     */
    public static function getAncestors(Category $descendant): array
    {
        $ancestors = [];

        if ($descendant->parent_id) {
            foreach (static::getAll() as $category) {
                if ($category->lft < $descendant->rgt) {
                    if ($category->rgt > $descendant->rgt) {
                        $ancestors[$category->id] = $category;
                    }

                    continue;
                }

                break;
            }
        }

        return $ancestors;
    }

    /**
     * @return array<int, Category>
     * @noinspection PhpUnused
     */
    public static function getChildren(Category $parent): array
    {
        if (!static::hasDescendants($parent)) {
            return [];
        }

        return array_filter(static::getAll(), fn(Category $category) => $category->parent_id == $parent->id);
    }

    /**
     * @return array<int, Category>
     * @noinspection PhpUnused
     */
    public static function getDescendants(Category $ancestor): array
    {
        if (!static::hasDescendants($ancestor)) {
            return [];
        }

        return array_filter(static::getAll(), fn(Category $category) => $category->lft > $ancestor->lft && $category->rgt < $ancestor->rgt);
    }

    /**
     * @return array<int, Category>
     * @noinspection PhpUnused
     */
    public static function getByEntry(Entry $entry): array
    {
        $categoryIds = $entry->getCategoryIds();

        return array_filter(static::getAll(), fn(Category $category) => $category->hasEntriesEnabled()
            && in_array($category->id, $categoryIds));
    }

    /**
     * @return array<int, Category>
     */
    public static function findAll(): array
    {
        return Category::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->whereStatus()
            ->indexBy('id')
            ->all();
    }

    public static function hasDescendants(Category $ancestor): bool
    {
        return $ancestor->lft < $ancestor->rgt + 1;
    }

    public static function invalidateCache(): void
    {
        if (static::getModule()->categoryCachedQueryDuration !== false) {
            TagDependency::invalidate(Yii::$app->getCache(), static::CACHE_KEY);
        }
    }
}