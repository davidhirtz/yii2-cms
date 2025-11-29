<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\TextareaField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\TinyMceField;
use Stringable;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;

/**
 * @property Asset|Category|Entry|Section $model
 */
abstract class ActiveForm extends \davidhirtz\yii2\skeleton\widgets\forms\ActiveForm
{
    protected function getStatusField(): ?Stringable
    {
        return SelectField::make()
            ->model($this->model)
            ->property('status')
            ->items(ArrayHelper::getColumn($this->model::getStatuses(), 'name'));
    }

    protected function getTypeField(): ?Stringable
    {
        return SelectField::make()
            ->model($this->model)
            ->property('type')
            ->items(ArrayHelper::getColumn($this->model::getTypes(), 'name'));
    }

    protected function getNameField(): ?Stringable
    {
        return InputField::make()
            ->property('name');
    }

    protected function getContentField(): ?Stringable
    {
        if (!$this->model->contentType) {
            return null;
        }

        return $this->model->contentType === 'html'
            ? TinyMceField::make()
                ->property('content')
                ->validator($this->model->htmlValidator)
            : TextareaField::make()
                ->property('content');
    }
}
