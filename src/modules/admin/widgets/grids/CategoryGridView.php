<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Controllers\CategoryController;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\CategoryGridTrait;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\CreateButton;
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
    public function configure(): void
    {
        $this->attributes['id'] ??= 'categories';
        $this->model ??= Category::instance();

        if ($this->provider->category) {
            $this->layout = '{items}';
        }

        /** @see CategoryController::actionOrder() */
        $this->orderRoute = ['order', 'id' => $this->provider->category->id ?? null];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getBranchCountColumn(),
            $this->getEntryCountColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        $this->initAncestors();
        $this->initHeader();

        $this->footer ??= [
            $this->getCreateCategoryButton(),
        ];

        parent::configure();
    }

    protected function getCreateCategoryButton(): ?Stringable
    {
        if (!Yii::$app->getUser()->can(Category::AUTH_CATEGORY_CREATE)) {
            return null;
        }

        return CreateButton::make()
            ->text(Yii::t('cms', 'Create Category'))
            ->href(['/admin/category/create', 'id' => $this->provider->category->id ?? null]);
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
