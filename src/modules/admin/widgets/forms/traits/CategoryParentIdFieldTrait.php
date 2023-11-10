<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveField;
use Yii;
use yii\helpers\ArrayHelper;

trait CategoryParentIdFieldTrait
{
    use ParentIdFieldTrait;

    private ?array $_categories = null;

    public function parentIdField(array $options = []): ActiveField|string
    {
        if (!static::getModule()->enableNestedCategories
            || !$this->model->hasParentEnabled()
            || !$this->getCategories()) {
            return '';
        }

        return $this->field($this->model, 'parent_id', $options)
            ->dropDownList($this->getParentIdItems(), $this->getParentIdOptions());
    }

    protected function getParentIdItems(): array
    {
        return Category::indentNestedTree($this->getCategories(), $this->model->getI18nAttributeName('name'));
    }

    protected function getParentIdOptions(array $options = []): array
    {
        $defaultOptions = [
            ...$this->getOptionsForDisabledDescendants(),
            ...$this->getOptionsForParentSlugDataValue(),
        ];

        $defaultOptions['prompt']['text'] ??= '';
        $defaultOptions['prompt']['options'] ??= [];

        return ArrayHelper::merge($defaultOptions, $options);
    }

    protected function getOptionsForDisabledDescendants(array $options = []): array
    {
        foreach ($this->getCategories() as $category) {
            if ($category->lft >= $this->model->lft && $category->rgt <= $this->model->rgt) {
                $options['options'][$category->id]['disabled'] = true;
            }
        }

        return $options;
    }

    protected function getOptionsForParentSlugDataValue(array $options = []): array
    {
        if (!in_array('parent_id', $this->model->slugTargetAttribute)) {
            return $options;
        }

        $attributeNames = $this->model->getI18nAttributeNames('slug');

        foreach ($attributeNames as $language => $attributeName) {
            $options['data-form-target'][] = $this->getSlugId($language);
            $options['prompt']['options']['data-value'][] = $this->getSlugBaseUrl($language);
        }

        foreach ($this->getCategories() as $category) {
            foreach ($attributeNames as $language => $attributeName) {
                $options['options'][$category->id]['data-value'][] = $this->getParentIdOptionDataValue($category, $language);
            }
        }

        return $options;
    }

    public function getSlugBaseUrl(?string $language = null): string
    {
        if (!$this->model->getRoute()) {
            return '';
        }

        return Yii::$app->getI18n()->callback($language, function () {
            $route = [...$this->model->getRoute(), 'category' => ''];
            $url = Yii::$app->getUrlManager()->createAbsoluteUrl($route);
            $url = rtrim($url, '/');

            if (!str_ends_with($url, '=')) {
                $url .= '/';
            }

            return $url;
        });
    }

    /**
     * @return Category[]
     */
    protected function getCategories(): array
    {
        if ($this->_categories === null) {
            $entries = Category::find()
                ->select($this->model->getI18nAttributesNames(['id', 'status', 'parent_id', 'name', 'lft', 'rgt', 'entry_count']))
                ->whereHasDescendantsEnabled()
                ->indexBy('id')
                ->all();

            $this->_categories = array_filter($entries, fn(Category $category): bool => $category->hasDescendantsEnabled());
        }

        return $this->_categories;
    }
}