<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class AssetActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
 *
 * @property Asset $model
 */
class AssetActiveForm extends ActiveForm
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                ['thumbnail'],
                ['-'],
                ['status', 'dropDownList', ArrayHelper::getColumn(Asset::getStatuses(), 'name')],
                ['type', 'dropDownList', ArrayHelper::getColumn(Asset::getTypes(), 'name')],
                ['name'],
                ['content'],
                ['alt_text'],
                ['link'],
            ];
        }


        parent::init();
    }

    /**
     * @return string
     */
    public function thumbnailField()
    {
        $file = $this->model->file;
        return $file->hasPreview() ? $this->row($this->offset(Html::img($file->folder->getUploadUrl() . $file->getFilename(), ['class' => 'img-transparent']))) : '';
    }
}