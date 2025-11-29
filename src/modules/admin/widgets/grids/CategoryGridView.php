<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\controllers\CategoryController;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\traits\CategoryGridTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\DraggableSortGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\ViewGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\CreateButton;
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

    public string $categoryParamName = 'id';
    public bool $showUrl = true;

    #[Override]
    public function configure(): void
    {
        $this->attributes['id'] ??= 'categories';
        $this->model ??= Category::instance();

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
        return TimeagoColumn::make()
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
