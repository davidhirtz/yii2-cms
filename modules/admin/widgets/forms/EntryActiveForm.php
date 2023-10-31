<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTimeInput;
use davidhirtz\yii2\skeleton\widgets\jui\DatePicker;
use Yii;
use yii\widgets\ActiveField;

/**
 * EntryActiveForm is a widget that builds an interactive HTML form for {@see Entry}. By default, it implements fields
 * only for default attributes defined in the base model.
 *
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use ModuleTrait;

    public bool $hasStickyButtons = true;

    public function init(): void
    {
        $this->fields ??= [
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

        parent::init();
    }

    /**
     * @noinspection PhpUnused {@see static::init()}
     */
    public function publishDateField(): ActiveField|string
    {
        return $this->field($this->model, 'publish_date')->widget(DateTimeInput::class);
    }
}