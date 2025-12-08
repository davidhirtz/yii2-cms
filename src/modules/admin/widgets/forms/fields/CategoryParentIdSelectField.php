<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\collections\CategoryCollection;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ParentIdSelectFieldTrait;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;

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
            $this->indent
        );

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
