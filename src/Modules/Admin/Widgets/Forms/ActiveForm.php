<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TextareaField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
use Stringable;
use Hirtz\Skeleton\Helpers\ArrayHelper;

/**
 * @property Asset|Category|Entry|Section $model
 */
abstract class ActiveForm extends \Hirtz\Skeleton\Widgets\Forms\ActiveForm
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
