<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\modules\admin\widgets\forms\fields\CategoryParentIdSelectField;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use Stringable;
use Yii;

trait CategoryParentIdFieldTrait
{
    use ModuleTrait;

    protected function getParentIdField(): ?Stringable
    {
        if (!static::getModule()->enableNestedCategories || !$this->model->hasParentEnabled()) {
            return null;
        }

        return CategoryParentIdSelectField::make()
            ->attributes($this->getParentIdAttributes());
    }

    protected function getParentIdAttributes(): array
    {
        $options = [
            'prompt' => [
                'text' => '',
                'options' => [],
            ],
        ];

        if (
            !$this->model->slugTargetAttribute
            || !in_array('parent_id', (array)$this->model->slugTargetAttribute, true)
        ) {
            return $options;
        }

        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attributeName) {
            $options['data-form-target'][] = $this->getSlugId($language);
            $options['prompt']['options']['data-value'][] = $this->getSlugBaseUrl($language);
        }


        return $options;
    }

    protected function getSlugBaseUrl(?string $language = null): string
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
