<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\modules\admin\widgets\forms\traits\AssetFieldsTrait;

/**
 * AssetActiveForm is a widget that builds an interactive HTML form for {@see Asset}. By default, it implements fields
 * for all safe attributes defined in the model.
 *
 * @property Asset $model
 */
class AssetActiveForm extends ActiveForm
{
    use AssetFieldsTrait;

    /**
     * @var bool
     */
    public $hasStickyButtons = true;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fields = $this->fields ?: array_diff($this->getDefaultFieldNames(), [
            'file_id',
            'entry_id',
            'section_id',
        ]);

        parent::init();
    }
}