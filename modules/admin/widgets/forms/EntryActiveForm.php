<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\jui\DatePicker;
use Yii;

/**
 * Class EntryActiveForm
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
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
                'status',
                'type',
                'name',
                'content',
                'publish_date',
                '-',
                'title',
                'description',
                'slug',
            ];
        }

        parent::init();
    }

    /**
     * @return \yii\bootstrap4\ActiveField|\yii\widgets\ActiveField
     */
    public function publishDateField()
    {
        return $this->field($this->model, 'publish_date')->appendInput(Yii::$app->getUser()->getIdentity()->getTimezoneOffset())->widget(DatePicker::class, $this->getPublishDateConfig());
    }

    /**
     * @return array
     */
    protected function getPublishDateConfig(): array
    {
        return [
            'options' => ['class' => 'form-control', 'autocomplete' => 'off'],
            'showTime' => true,
        ];
    }
}