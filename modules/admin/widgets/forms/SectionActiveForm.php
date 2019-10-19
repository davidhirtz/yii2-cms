<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Section;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class SectionActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
 *
 * @property Section $model
 */
class SectionActiveForm extends ActiveForm
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                ['status', 'dropDownList', ArrayHelper::getColumn(Section::getStatuses(), 'name')],
                ['type', 'dropDownList', ArrayHelper::getColumn(Section::getTypes(), 'name')],
                ['name'],
                ['content'],
                ['-'],
                ['slug', ['enableClientValidation' => false], 'url'],
            ];
        }

        parent::init();
    }

    /**
     * @param mixed $attribute can be used to customize the base url per attribute
     * @return string
     */
    public function getBaseUrl($attribute = null)
    {
        return trim(Url::to($this->model->entry->getRoute(), true), '/') . '#';
    }
}