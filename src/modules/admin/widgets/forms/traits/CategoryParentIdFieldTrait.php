<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\modules\admin\widgets\forms\fields\CategoryParentIdSelectField;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use Stringable;

trait CategoryParentIdFieldTrait
{
    use ModuleTrait;

    protected function getParentIdField(): ?Stringable
    {
        if (!static::getModule()->enableNestedCategories || !$this->model->hasParentEnabled()) {
            return null;
        }

        return CategoryParentIdSelectField::make()
            ->attributes($this->getParentIdAttributes())
            ->property('parent_id')
            ->prompt();
    }

    protected function getParentIdAttributes(): array
    {
        $attributes = [];

        if (
            !$this->model->slugTargetAttribute
            || !in_array('parent_id', (array)$this->model->slugTargetAttribute, true)
        ) {
            return $attributes;
        }

        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attributeName) {
            $attributes['data-form-target'][] = $this->getSlugId($language);
            $attributes['prompt']['options']['data-value'][] = $this->getSlugBaseUrl($language);
        }

        return $attributes;
    }
}
