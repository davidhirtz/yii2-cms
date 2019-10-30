<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\jui\DatePicker;
use Yii;
use yii\helpers\ArrayHelper;

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
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                ['status', 'dropDownList', ArrayHelper::getColumn($this->model::getStatuses(), 'name')],
                ['type', 'dropDownList', ArrayHelper::getColumn($this->model::getTypes(), 'name')],
                ['name'],
                ['content'],
                ['publish_date'],
                ['-'],
                ['title'],
                ['description', 'textarea'],
                ['slug', ['enableClientValidation' => false], 'slug'],
            ];
        }


        parent::init();
    }

    /**
     * @return \yii\bootstrap4\ActiveField|\yii\widgets\ActiveField
     */
    public function publishDateField()
    {
        return $this->field($this->model, 'publish_date')->appendInput(Yii::$app->getUser()->getIdentity()->getTimezoneOffset())->widget(DatePicker::class, [
            'options' => ['class' => 'form-control', 'autocomplete' => 'off'],
            'showTime' => true,
        ]);
    }
}