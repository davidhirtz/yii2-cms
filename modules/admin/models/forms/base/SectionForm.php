<?php

namespace davidhirtz\yii2\cms\modules\admin\models\forms\base;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\models\forms\AssetForm;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

/**
 * Class SectionForm
 * @package davidhirtz\yii2\cms\modules\admin\models\forms\base
 *
 * @property AssetForm[] $assets
 * @method static \davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm findOne($condition)
 */
class SectionForm extends Section
{

    /**
     * @return ActiveQuery
     */
    public function getAssets(): ActiveQuery
    {
        return $this->hasMany(AssetForm::class, ['section_id' => 'id'])
            ->with(['file', 'file.folder'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('section');
    }
}