<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\fields;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;

/**
 * @template T of Category
 * @property Category $model
 */
class CategoryParentIdSelectField extends SelectField
{
    use ModuleTrait;
    use ParentIdFieldTrait;

    protected function configure(): void
    {
        $this->property ??= 'parent_id';
        $this->prompt = '';

        $categories = $this->getCategories();

        $labels = Category::indentNestedTree(
            $categories,
            $this->model->getI18nAttributeName('name'),
            $this->indent);

        $attributeNames = $this->model->getI18nAttributeNames('slug');

        foreach ($categories as $category) {
            $attributes = ['label' => $labels[$category->id]];

            foreach ($attributeNames as $language => $attributeName) {
                $attributes['data-value'][] = $this->getParentIdOptionDataValue($category, $language);
            }

            if ($category->lft >= $this->model->lft && $category->rgt <= $this->model->rgt) {
                $attributes['disabled'] = true;
            }

            $this->addItem($category->id, $attributes);
        }

        parent::configure();
    }

    protected function getCategories(): array
    {
        return array_filter($this->findCategories(), fn (Category $category): bool => $category->hasDescendantsEnabled());
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
