<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\forms;

use Hirtz\Cms\models\Asset;
use Hirtz\Cms\models\Category;
use Hirtz\Cms\models\Entry;
use Hirtz\Cms\models\Section;
use Hirtz\Skeleton\widgets\forms\fields\InputField;
use Hirtz\Skeleton\widgets\forms\fields\SelectField;
use Hirtz\Skeleton\widgets\forms\fields\TextareaField;
use Hirtz\Skeleton\widgets\forms\fields\TinyMceField;
use Stringable;
use Hirtz\Skeleton\helpers\ArrayHelper;

/**
 * @property Asset|Category|Entry|Section $model
 */
abstract class ActiveForm extends \Hirtz\Skeleton\widgets\forms\ActiveForm
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
            ->property('type');
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
