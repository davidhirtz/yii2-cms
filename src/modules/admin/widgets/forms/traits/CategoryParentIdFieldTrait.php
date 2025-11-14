<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\modules\admin\widgets\forms\fields\CategoryParentIdDropDown;
use Yii;
use yii\widgets\ActiveField;

trait CategoryParentIdFieldTrait
{
    public function parentIdField(): ActiveField|string
    {
        if (!static::getModule()->enableNestedCategories || !$this->model->hasParentEnabled()) {
            return '';
        }

        return $this->field($this->model, 'parent_id')->widget(CategoryParentIdDropDown::class, [
            'options' => $this->getParentIdOptions(),
        ]);
    }

    protected function getParentIdOptions(): array
    {
        $options = [
            'prompt' => [
                'text' => '',
                'options' => [],
            ],
        ];

        if (!$this->model->slugTargetAttribute || !in_array('parent_id', (array)$this->model->slugTargetAttribute, true)) {
            return $options;
        }

        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attributeName) {
            $options['data-form-target'][] = $this->getSlugId($language);
            $options['prompt']['options']['data-value'][] = $this->getSlugBaseUrl($language);
        }


        return $options;
    }

    public function getSlugBaseUrl(?string $language = null): string
    {
        if (!$this->model->getRoute()) {
            return '';
        }

        return Yii::$app->getI18n()->callback($language, function (): string {
            $route = [...$this->model->getRoute(), 'category' => ''];
            $url = Yii::$app->getUrlManager()->createAbsoluteUrl($route);
            $url = rtrim($url, '/');

            if (!str_ends_with($url, '=')) {
                $url .= '/';
            }

            return $url;
        });
    }
}
