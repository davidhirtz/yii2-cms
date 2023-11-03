<?php

namespace davidhirtz\yii2\cms\models;

class CategoryCollection
{
    protected static ?array $_categories = null;

    /**
     * @return Category[]
     */
    public static function getAll(): array
    {
        static::$_categories ??= Category::find()
            ->indexBy('id')
            ->all();

        return static::$_categories;
    }
}