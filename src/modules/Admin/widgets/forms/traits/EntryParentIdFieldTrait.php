<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField;
use Hirtz\Cms\modules\ModuleTrait;
use Stringable;

/**
 * @template T of Entry
 */
trait EntryParentIdFieldTrait
{
    use ModuleTrait;

    protected function getParentIdField(): ?Stringable
    {
        if (!static::getModule()->enableNestedEntries || !$this->model->hasParentEnabled()) {
            return null;
        }

        return EntryParentIdSelectField::make()
            ->attributes($this->getParentIdAttributes())
            ->prompt();
    }

    protected function getParentIdAttributes(): array
    {
        $attributes = [];

        if (!in_array('parent_slug', (array)$this->model->slugTargetAttribute, true)) {
            return $attributes;
        }

        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
            $attributes['data-form-target'][] = '#' . $this->getSlugId($language);
            $attributes['promptAttributes']['data-value'][] = $this->getSlugBaseUrl($language);
        }

        return $attributes;
    }

}
