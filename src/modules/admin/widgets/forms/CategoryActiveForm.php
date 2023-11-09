<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\CategoryParentIdFieldTrait;

/**
 * @property Category $model
 */
class CategoryActiveForm extends ActiveForm
{
    use CategoryParentIdFieldTrait;

    /**
     * @uses static::statusField()
     * @uses static::parentIdField()
     * @uses static::typeField()
     * @uses static::contentField()
     * @uses static::descriptionField()
     * @uses static::slugField()
     */
    public function init(): void
    {
        $this->fields ??= [
            'status',
            'parent_id',
            'type',
            'name',
            'content',
            '-',
            'title',
            'description',
            'slug',
        ];

        parent::init();
    }
}