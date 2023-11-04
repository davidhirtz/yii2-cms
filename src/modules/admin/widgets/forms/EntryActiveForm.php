<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\EntryParentIdFieldTrait;
use davidhirtz\yii2\datetime\DateTimeInput;
use yii\widgets\ActiveField;

/**
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use EntryParentIdFieldTrait;

    /**
     * @uses static::statusField()
     * @uses static::typeField()
     * @uses static::parentIdField()
     * @uses static::contentField()
     * @uses static::publishDateField()
     * @uses static::descriptionField()
     * @uses static::slugField()
     */
    public function init(): void
    {
        $this->fields ??= [
            'status',
            'type',
            'parentId',
            'name',
            'content',
            'publishDate',
            '-',
            'title',
            'description',
            'slug',
        ];

        parent::init();
    }

    public function publishDateField(): ActiveField|string
    {
        return $this->field($this->model, 'publish_date')->widget(DateTimeInput::class);
    }

    public function slugField(array $options = []): ActiveField|string
    {
        if ($this->model->isIndex() && $this->model->isEnabled()) {
            return '';
        }

        return parent::slugField($options);
    }
}