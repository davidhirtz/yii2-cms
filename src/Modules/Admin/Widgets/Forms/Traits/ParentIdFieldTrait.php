<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Stringable;

/**
 * The entry and category parent fields differed only because entries keyed off `parent_slug` and categories off
 * `parent_id` in the removed `$slugTargetAttribute`. Both now key off `hasPermalink()`, so one trait serves both.
 */
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

    /**
     * Wires the parent select to the slug field, so picking a parent updates the URL prefix shown in front of it.
     */
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
