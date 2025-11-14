<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\controllers\CategoryController;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\traits\CategoryGridTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\CreateButton;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\DraggableSortButton;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\ViewButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\timeago\Timeago;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<Category>
 * @property CategoryActiveDataProvider $dataProvider
 */
class CategoryGridView extends GridView
{
    use CategoryGridTrait;
    use ModuleTrait;

    public string $categoryParamName = 'id';
    public bool $showUrl = true;

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'category-grid');

        $this->columns ??= [
            $this->statusColumn(),
            $this->typeColumn(),
            $this->nameColumn(),
            $this->branchCountColumn(),
            $this->entryCountColumn(),
            $this->updatedAtColumn(),
            $this->buttonsColumn(),
        ];

        if ($this->dataProvider->category) {
            /** @see CategoryController::actionOrder() */
            $this->orderRoute = ['order', 'id' => $this->dataProvider->category->id];
        }

        $this->initAncestors();

        parent::init();
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                $this->getCreateCategoryButton(),
            ],
        ];
    }

    protected function getCreateCategoryButton(): ?Stringable
    {
        if (!Yii::$app->getUser()->can(Category::AUTH_CATEGORY_CREATE)) {
            return null;
        }

        return Yii::createObject(CreateButton::class, [
            Yii::t('cms', 'New Category'),
            ['/admin/category/create', 'id' => $this->dataProvider->category->id ?? null]
        ]);
    }

    public function updatedAtColumn(): array
    {
        return [
            'attribute' => 'updated_at',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => fn (Category $category) => $this->dateFormat ? $category->updated_at->format($this->dateFormat) : Timeago::tag($category->updated_at)
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => function (Category $category): array {
                $buttons = [];

                if ($this->isSortable()) {
                    $buttons[] = Yii::createObject(DraggableSortButton::class);
                }

                $buttons[] = Yii::createObject(ViewButton::class, [$category]);
                return $buttons;
            }
        ];
    }
}
