<?php

namespace davidhirtz\yii2\cms\modules\admin\models\forms\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\modules\admin\models\forms\AssetForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use yii\behaviors\SluggableBehavior;

/**
 * Class EntryForm
 * @package davidhirtz\yii2\cms\modules\admin\models\forms\base
 *
 * @property SectionForm[] $sections
 * @property AssetForm[] $assets
 * @method static \davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm findOne($condition)
 */
class EntryForm extends Entry
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
                    'targetAttribute' => static::getModule()->enabledNestedEntries ? ['slug', 'parent_id'] : null,
                ],
            ],
        ]);
    }

    /**
     * @return SectionQuery
     */
    public function getSections(): SectionQuery
    {
        return $this->hasMany(SectionForm::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }

    /**
     * @return AssetQuery
     */
    public function getAssets(): AssetQuery
    {
        return $this->hasMany(AssetForm::class, ['entry_id' => 'id'])
            ->andWhere(['section_id' => null])
            ->with(['file', 'file.folder'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }
}