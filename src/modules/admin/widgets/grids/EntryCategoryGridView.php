<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\traits\CategoryGridTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\timeago\Timeago;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<Category>
 * @property CategoryActiveDataProvider $dataProvider
 */
class EntryCategoryGridView extends GridView
{
    use CategoryGridTrait;
    use ModuleTrait;

    /**
     * @var string the category param name used in urls on {@see CategoryGridTrait}
     */
    public string $categoryParamName = 'category';

    /**
     * @var bool whether frontend url should be displayed, defaults to false
     */
    public bool $showUrl = false;

    #[\Override]
    public function init(): void
    {
        if (!$this->rowOptions) {
            $this->rowOptions = fn (Category $category) => [
                'class' => $category->entryCategory ? 'is-selected' : null,
            ];
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

    public function updatedAtColumn(): array
    {
        return [
            'label' => EntryCategory::instance()->getAttributeLabel('updated_at'),
            'headerOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => fn (Category $category) => $category->entryCategory
                ? ($this->dateFormat
                    ? $category->entryCategory->updated_at->format($this->dateFormat)
                    : Timeago::tag($category->entryCategory->updated_at))
                : null
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => function (Category $category): string {
                // Make sure categories can always be removed even if they were not supposed to have entries enabled.
                if (!$category->hasEntriesEnabled() && !$category->entryCategory) {
                    return '';
                }

                $route = [
                    $category->entryCategory ? 'delete' : 'create',
                    'entry' => $this->dataProvider->entry->id,
                    'category' => $category->id,
                ];

                return Html::buttons(Html::a((string)Icon::tag($category->entryCategory ? 'ban' : 'star'), $route, [
                    'class' => 'btn btn-primary',
                    'data-method' => 'post',
                ]));
            }
        ];
    }

    #[\Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['category/update', 'id' => $model->id];
    }
}
