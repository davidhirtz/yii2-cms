<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Toolbars;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Override;
use Yii;

class CategoryFilterDropdown extends FilterDropdown
{
    #[Override]
    protected function configure(): void
    {
        $this->label ??= Yii::t('cms', 'All Categories');
        $this->paramName ??= 'category';

        $this->items = array_map(fn ($category) => $this->getNestedCategoryNames()[$category->id], $this->getCategories());

        parent::configure();
    }

    protected function getNestedCategoryNames(): array
    {
        return Category::indentNestedTree(
            $this->getCategories(),
            Category::instance()->getI18nAttributeName('name'),
        );
    }

    protected function getCategories(): array
    {
        return CategoryCollection::getAll();
    }
}
