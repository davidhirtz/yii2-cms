<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\fields\EntryParentIdSelectField;
use davidhirtz\yii2\cms\modules\ModuleTrait;
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
            ->property('parent_id')
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
