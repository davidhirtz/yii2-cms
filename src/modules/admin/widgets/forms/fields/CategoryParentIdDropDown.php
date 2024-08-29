<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\fields;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use yii\widgets\InputWidget;

/**
 * @template T of Category
 * @property Category $model
 */
class CategoryParentIdDropDown extends InputWidget
{
    use ModuleTrait;
    use ParentIdFieldTrait;

    /**
     * @var array|Category[]
     */
    private array $_categories;

    public function init(): void
    {
        $this->items = Category::indentNestedTree($this->getCategories(), $this->model->getI18nAttributeName('name'), $this->indent);
        $this->prepareOptions();

        parent::init();
    }

    protected function prepareOptions(): void
    {
        $attributeNames = $this->model->getI18nAttributeNames('slug');

        foreach ($this->getCategories() as $category) {
            foreach ($attributeNames as $language => $attributeName) {
                $this->options['options'][$category->id]['data-value'][] = $this->getParentIdOptionDataValue($category, $language);
            }

            if ($category->lft >= $this->model->lft && $category->rgt <= $this->model->rgt) {
                $this->options['options'][$category->id]['disabled'] = true;
            }
        }
    }

    /**
     * @return T[]
     */
    protected function getCategories(): array
    {
        $this->_categories ??= array_filter($this->findCategories(), fn (Category $category): bool => $category->hasDescendantsEnabled());
        return $this->_categories;
    }

    /**
     * @return T[]
     */
    protected function findCategories(): array
    {
        return $this->getCategoryQuery()
            ->whereHasDescendantsEnabled()
            ->indexBy('id')
            ->all();
    }

    protected function getCategoryQuery(): CategoryQuery
    {
        return Category::find()
            ->replaceI18nAttributes();
    }
}
