<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\CategoryParentIdSelectField;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;

trait CategoryParentIdFieldTrait
{
    use ModuleTrait;
    use ParentIdFieldTrait;

    #[Override]
    protected function createParentIdSelectField(): SelectField
    {
        return CategoryParentIdSelectField::make();
    }

    #[Override]
    protected function hasParentIdField(): bool
    {
        return static::getModule()->enableNestedCategories && $this->model->hasParentEnabled();
    }
}
