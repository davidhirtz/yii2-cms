<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\fields;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\collections\CategoryCollection;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\ParentIdSelectFieldTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;

/**
 * @template T of Category
 * @property T $model
 */
class CategoryParentIdSelectField extends SelectField
{
    use ModuleTrait;
    use ParentIdSelectFieldTrait;

    protected function configure(): void
    {
        $categories = $this->getCategories();

        $labels = Category::indentNestedTree(
            $categories,
            $this->model->getI18nAttributeName('name'),
            $this->indent);

        $attributeNames = $this->model->getI18nAttributeNames('slug');

        foreach ($categories as $category) {
            $item = [
                'label' => $labels[$category->id],
                'disabled' => $category->lft >= $this->model->lft && $category->rgt <= $this->model->rgt,
            ];

            foreach ($attributeNames as $language => $attributeName) {
                $item['data-value'][] = $this->getParentIdOptionDataValue($category, $language);
            }

            $this->addItem($category->id, $item);
        }

        parent::configure();
    }

    protected function getCategories(): array
    {
        return array_filter(CategoryCollection::getAll(), fn (Category $category): bool => $category->hasDescendantsEnabled());
    }
}
