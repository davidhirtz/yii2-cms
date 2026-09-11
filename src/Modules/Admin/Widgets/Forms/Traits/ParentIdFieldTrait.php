<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Stringable;

trait ParentIdFieldTrait
{
    protected function getParentIdField(): ?Stringable
    {
        if (!$this->hasParentIdField()) {
            return null;
        }

        return $this->createParentIdSelectField()
            ->attributes($this->getParentIdAttributes())
            ->property('parent_id')
            ->prompt();
    }

    abstract protected function createParentIdSelectField(): SelectField;

    abstract protected function hasParentIdField(): bool;

    protected function getParentIdAttributes(): array
    {
        return [];
    }
}
