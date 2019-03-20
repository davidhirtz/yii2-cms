<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\base;

use davidhirtz\yii2\cms\modules\admin\models\forms\PageForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use Yii;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;
use yii\web\JsExpression;

/**
 * Class PageActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 *
 * @property PageForm $model
 */
class PageActiveForm extends ActiveForm
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
                ['status', 'dropDownList', ArrayHelper::getColumn(PageForm::getStatuses(), 'name')],
                ['type', 'dropDownList', ArrayHelper::getColumn(PageForm::getTypes(), 'name')],
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

    /**
     * @param array $options
     * @return \yii\bootstrap4\ActiveField|\yii\widgets\ActiveField
     */
    public function publishDateField()
    {
        return $this->field($this->model, 'publish_date', ['inputTemplate' => '<div class="input-group">{input}<div class="input-group-append"><span class="input-group-text">' . Yii::$app->getUser()->getIdentity()->getTimezoneOffset() . '</span></div></div>'])->widget(DatePicker::class, [
            'options' => ['class' => 'form-control', 'autocomplete' => 'off'],
            'language' => Yii::$app->language,
            'dateFormat' => 'php:Y-m-d H:i',
            'clientOptions' => [
                'onSelect' => new JsExpression('function(t){$(this).val(t.slice(0, 10)+" 00:00");}'),
            ]
        ]);
    }
}