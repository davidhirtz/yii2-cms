<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTimeInput;

/**
 * EntryActiveForm is a widget that builds an interactive HTML form for {@see Entry}. By default, it implements fields
 * only for default attributes defined in the base model.
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
    public $hasStickyButtons = true;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fields = $this->fields ?: [
            'status',
            'type',
            'name',
            'content',
            ['publish_date', DateTimeInput::class],
            '-',
            'title',
            'description',
            'slug',
        ];

        parent::init();
    }
}