<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TextareaField;
use Stringable;

trait MetaFieldsTrait
{
    protected function getTitleField(): ?Stringable
    {
        return InputField::make()
            ->property('title');
    }

    public function getDescriptionField(): ?Stringable
    {
        return TextareaField::make()
            ->property('description')
            ->addStyle(['min-height' => '4.5rem'], false);
    }
}
