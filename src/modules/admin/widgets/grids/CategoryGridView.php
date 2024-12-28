<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\controllers\CategoryController;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\traits\CategoryGridTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\timeago\Timeago;
use Yii;

/**
 * @extends GridView<Category>
 * @property CategoryActiveDataProvider $dataProvider
 */
class CategoryGridView extends GridView
{
    use CategoryGridTrait;
    use ModuleTrait;

    /**
     * @var string the category param name used in urls on {@see CategoryGridTrait}.
     */
    public string $categoryParamName = 'id';

    /**
     * @var bool whether frontend url should be displayed, defaults to true
     */
    public bool $showUrl = true;

    public function init(): void
    {
        if ($this->dataProvider->category) {
            /** @see CategoryController::actionOrder() */
            $this->orderRoute = ['order', 'id' => $this->dataProvider->category->id];
        }

        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->typeColumn(),
                $this->nameColumn(),
                $this->branchCountColumn(),
                $this->entryCountColumn(),
                $this->updatedAtColumn(),
                $this->buttonsColumn(),
            ];
        }

        $this->initAncestors();

        parent::init();
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                [
                    'content' => $this->getCreateCategoryButton(),
                    'visible' => Yii::$app->getUser()->can(Category::AUTH_CATEGORY_CREATE),
                    'options' => ['class' => 'col'],
                ],
            ],
        ];
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
            'contentOptions' => ['class' => 'text-end text-nowrap'],
            'content' => function (Category $category): string {
                $buttons = [];

                if ($this->isSortedByPosition()) {
                    $buttons[] = Html::tag('span', (string)Icon::tag('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
                }

                $buttons[] = Html::a((string)Icon::tag('wrench'), ['update', 'id' => $category->id], ['class' => 'btn btn-primary d-none d-md-inline-block']);
                return Html::buttons($buttons);
            }
        ];
    }

    protected function getCreateCategoryButton(): string
    {
        $route = ['/admin/category/create', 'id' => $this->dataProvider->category->id ?? null];

        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Category')), $route, [
            'class' => 'btn btn-primary'
        ]);
    }

    public function isSortedByPosition(): bool
    {
        return parent::isSortedByPosition()
            && !$this->dataProvider->searchString
            && count($this->dataProvider->getModels()) > 1;
    }
}
