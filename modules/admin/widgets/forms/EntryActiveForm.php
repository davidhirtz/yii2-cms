<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use Yii;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;
use yii\web\JsExpression;

/**
 * Class EntryActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 *
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use ModuleTrait;

    /**
     * @var int
     */
    public $slugMaxLength = 20;

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
                ['status', 'dropDownList', ArrayHelper::getColumn($this->model::getStatuses(), 'name')],
                ['type', 'dropDownList', ArrayHelper::getColumn($this->model::getTypes(), 'name')],
                ['name'],
                ['content', ['options' => ['style' => !$this->model->contentType ? 'display:none' : null]], $this->model->contentType === 'html' ? CKEditor::class : 'textarea', ['validator' => $this->model->contentType === 'html' ? $this->model->htmlValidator : null]],
                ['publish_date'],
                ['-'],
                ['title'],
                ['description', 'textarea'],
                ['slug', ['enableClientValidation' => false], 'url'],
            ];
        }


        parent::init();
    }

    /**
     * @return \yii\bootstrap4\ActiveField|\yii\widgets\ActiveField
     */
    public function publishDateField()
    {
        return $this->field($this->model, 'publish_date', ['inputTemplate' => $this->appendInput(Yii::$app->getUser()->getIdentity()->getTimezoneOffset())])->widget(DatePicker::class, [
            'options' => ['class' => 'form-control', 'autocomplete' => 'off'],
            'language' => Yii::$app->language,
            'dateFormat' => 'php:Y-m-d H:i',
            'clientOptions' => [
                'onSelect' => new JsExpression('function(t){$(this).val(t.slice(0, 10)+" 00:00");}'),
            ]
        ]);
    }
}