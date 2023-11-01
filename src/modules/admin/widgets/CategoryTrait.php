<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets;

use davidhirtz\yii2\cms\models\Category;

trait CategoryTrait
{
    /**
     * @var Category[]
     */
    protected static ?array $_categories = null;

    /**
     * @return Category[]
     */
    public static function getCategories(): array
    {
        if (static::$_categories === null) {
            static::$_categories = Category::find()
                ->indexBy('id')
                ->all();
        }

        return static::$_categories;
    }
}