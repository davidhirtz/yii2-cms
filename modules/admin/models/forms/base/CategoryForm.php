<?php

namespace davidhirtz\yii2\cms\modules\admin\models\forms\base;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\models\forms\AssetForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use yii\behaviors\SluggableBehavior;

/**
 * Class CategoryForm
 * @package davidhirtz\yii2\cms\modules\admin\models\forms\base
 *
 * @property SectionForm[] $sections
 * @property AssetForm[] $assets
 * @method static \davidhirtz\yii2\cms\modules\admin\models\forms\CategoryForm findOne($condition)
 */
class CategoryForm extends Category
{
    /**
     * @return array
     */
    public function behaviors(): array
    {
        return $this->customSlugBehavior ? parent::behaviors() : array_merge(parent::behaviors(), [
            'SluggableBehavior' => [
                'class' => SluggableBehavior::class,
                'attribute' => 'name',
                'immutable' => true,
                'ensureUnique' => true,
                'uniqueValidator' => [
                    'targetAttribute' => ['slug', 'parent_id'],
                ],
            ],
        ]);
    }
}