<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TextareaField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
use Stringable;

trait ActiveFormFieldsTrait
{
    protected function getStatusField(): ?Stringable
    {
        return SelectField::make()
            ->property('status');
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

    protected function getLinkField(): ?Stringable
    {
        return InputField::make()
            ->property('link');
    }
}
