<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class AssetActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 *
 * @property Asset $model
 */
class AssetActiveForm extends ActiveForm
{
    /**
     * @var bool
     */
    public $showUnsafeAttributes = true;

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
                ['content', ['options' => ['style' => !$this->model->contentType ? 'display:none' : null]], $this->model->contentType === 'html' ? CKEditor::class : 'textarea'],
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
        $file=$this->model->file;
        return $file->hasPreview() ? $this->row($this->offset(Html::img($file->folder->getUploadUrl() . $file->getFilename()))) : '';
    }
}