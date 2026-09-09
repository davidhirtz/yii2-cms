<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;

/**
 * @template T of Entry
 */
trait EntryParentIdFieldTrait
{
    use ModuleTrait;
    use ParentIdFieldTrait;

    #[Override]
    protected function createParentIdSelectField(): SelectField
    {
        return EntryParentIdSelectField::make();
    }

    #[Override]
    protected function hasParentIdField(): bool
    {
        return static::getModule()->enableNestedEntries && $this->model->hasParentEnabled();
    }
}
