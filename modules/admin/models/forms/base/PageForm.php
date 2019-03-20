<?php

namespace davidhirtz\yii2\cms\modules\admin\models\forms\base;

use davidhirtz\yii2\cms\models\Page;
use yii\behaviors\SluggableBehavior;

/**
 * Class PageForm
 * @package davidhirtz\yii2\cms\modules\admin\models\forms\base
 *
 * @method static \davidhirtz\yii2\cms\modules\admin\models\forms\PageForm findOne($condition)
 */
class PageForm extends Page
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