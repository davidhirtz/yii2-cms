<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Controllers\CategoryController;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Traits\CategoryGridTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridSearchForm;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<Category>
 * @property CategoryActiveDataProvider $provider
 */
class CategoryGridView extends GridView
{
    use CategoryGridTrait;
    use ModuleTrait;

    public string $categoryParamName = 'parent';
    public bool $showUrl = true;

    #[Override]
    protected function configure(): void
    {
        $this->initAncestors();

        $this->attributes['id'] ??= 'category-grid-view';

        /** @see CategoryController::actionOrder() */
        $this->orderRoute = ['order', 'id' => $this->provider->category->id ?? null];

        $this->header ??= [
            $this->getCategoryDropdown(),
            $this->getSearchInput(),
        ];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getBranchCountColumn(),
            $this->getEntryCountColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        $this->footer ??= [
            $this->getCreateCategoryButton(),
        ];

        parent::configure();
    }

    protected function getCategoryDropdown(): ?FilterDropdown
    {
        return $this->provider->category
            ? FilterDropdown::make()
                ->items($this->getCategoryDropdownItems($this->provider->category))
                ->label($this->provider->category->getI18nAttribute('name'))
                ->paramName('parent')
            : null;
    }

    protected function getCategoryDropdownItems(Category $category): array
    {
        $attribute = Category::instance()->getI18nAttributeName('name');
        return Category::indentNestedTree($category->getAncestors() + [$category], $attribute);
    }

    protected function getCreateCategoryButton(): string|Stringable
    {
        return CreateButton::make()
            ->url(['/admin/cms/category/create', 'id' => $this->provider->category->id ?? null])
            ->roles([Category::AUTH_CATEGORY_CREATE])
            ->label(Yii::t('cms', 'Create Category'));
    }

    protected function getUpdatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('updated_at');
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(Category $category): array
    {
        $buttons = [];

        if ($this->isSortable()) {
            $buttons[] = DraggableSortGridButton::make();
        }

        $buttons[] = ViewGridButton::make()
            ->model($category);

        return $buttons;
    }
}
