<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets;

use davidhirtz\yii2\cms\modules\admin\models\forms\CategoryForm;

trait CategoryTrait{

    /**
     * @var CategoryForm[]
     */
    protected static $_categories;

    /**
     * @return CategoryForm[]
     */
    public static function getCategories()
    {
        if (static::$_categories === null) {
            static::$_categories = CategoryForm::find()
                ->replaceI18nAttributes()
                ->all();
        }

        return static::$_categories;
    }
}