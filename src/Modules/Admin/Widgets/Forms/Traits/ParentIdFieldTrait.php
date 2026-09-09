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
        $attributes = [];

        if (!$this->model->hasPermalink()) {
            return $attributes;
        }

        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
            $attributes['data-form-target'][] = '#' . $this->getSlugId($language);
            $attributes['promptAttributes']['data-value'][] = $this->getSlugBaseUrl($language);
        }

        return $attributes;
    }
}
