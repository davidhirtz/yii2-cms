<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\grids;

use Hirtz\Cms\models\Category;
use Hirtz\Cms\modules\admin\controllers\CategoryController;
use Hirtz\Cms\modules\admin\data\CategoryActiveDataProvider;
use Hirtz\Cms\modules\admin\widgets\grids\traits\CategoryGridTrait;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\widgets\grids\columns\ButtonColumn;
use Hirtz\Skeleton\widgets\grids\columns\buttons\DraggableSortGridButton;
use Hirtz\Skeleton\widgets\grids\columns\buttons\ViewGridButton;
use Hirtz\Skeleton\widgets\grids\columns\Column;
use Hirtz\Skeleton\widgets\grids\columns\RelativeTimeColumn;
use Hirtz\Skeleton\widgets\grids\GridView;
use Hirtz\Skeleton\widgets\grids\toolbars\CreateButton;
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
