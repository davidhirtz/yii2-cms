<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets;

use davidhirtz\yii2\cms\models\Category;

trait CategoryTrait{

    /**
     * @var Category[]
     */
    protected static $_categories;

    /**
     * @return Category[]
     */
    public static function getCategories()
    {
        if (static::$_categories === null) {
            static::$_categories = Category::find()
                ->replaceI18nAttributes()
                ->all();
        }

        return static::$_categories;
    }
}