<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\fields\EntryParentIdDropDown;
use yii\widgets\ActiveField;

/**
 * @template T of Entry
 */
trait EntryParentIdFieldTrait
{
    public function parentIdField(): ActiveField|string
    {
        if (!static::getModule()->enableNestedEntries || !$this->model->hasParentEnabled()) {
            return '';
        }

        return $this->field($this->model, 'parent_id')->widget(EntryParentIdDropDown::class, [
            'options' => $this->getParentIdOptions(),
        ]);
    }

    protected function getParentIdOptions(): array
    {
        $options = [
            'class' => 'form-select',
            'encode' => false,
            'prompt' => [
                'text' => '',
                'options' => [],
            ],
        ];

        if (!in_array('parent_slug', (array)$this->model->slugTargetAttribute)) {
            return $options;
        }

        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
            $options['data-form-target'][] = '#' . $this->getSlugId($language);
            $options['prompt']['options']['data-value'][] = $this->getSlugBaseUrl($language);
        }

        return $options;
    }
}
