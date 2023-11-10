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

            static::$_categories = $duration > 0
                ? Yii::$app->getDb()->cache(static::findAll(...), $duration, $dependency)
                : static::findAll();
        }

        return static::$_categories;
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
    protected static function findAll(): array
    {
        return Category::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->whereStatus()
            ->indexBy('id')
            ->all();
    }

    public static function invalidateCache(): void
    {
        if (static::getModule()->categoryCachedQueryDuration > 0) {
            TagDependency::invalidate(Yii::$app->getCache(), CategoryCollection::CACHE_KEY);
        }
    }
}