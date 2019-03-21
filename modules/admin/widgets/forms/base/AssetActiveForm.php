<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\base;

use davidhirtz\yii2\cms\modules\admin\models\forms\AssetForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use Yii;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;
use yii\web\JsExpression;

/**
 * Class AssetActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 *
 * @property AssetForm $model
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
                ['status', 'dropDownList', ArrayHelper::getColumn(AssetForm::getStatuses(), 'name')],
                ['type', 'dropDownList', ArrayHelper::getColumn(AssetForm::getTypes(), 'name')],
                ['name'],
                ['content', ['options' => ['style' => !$this->model->contentType ? 'display:none' : null]], $this->model->contentType === 'html' ? CKEditor::class : 'textarea'],
                ['publish_date'],
                ['-'],
                ['title'],
                ['description', 'textarea'],
                ['slug', ['enableClientValidation' => false], 'url'],
            ];
        }


        parent::init();
    }
}