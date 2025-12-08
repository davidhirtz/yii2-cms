<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\grids;

use Hirtz\Cms\models\Category;
use Hirtz\Cms\models\EntryCategory;
use Hirtz\Cms\modules\admin\data\CategoryActiveDataProvider;
use Hirtz\Cms\modules\admin\widgets\grids\traits\CategoryGridTrait;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\widgets\grids\columns\ButtonsColumn;
use Hirtz\Skeleton\widgets\grids\GridView;
use Hirtz\Timeago\Timeago;
use Override;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<Category>
 * @property CategoryActiveDataProvider $dataProvider
 */
class EntryCategoryGridView extends GridView
{
    use CategoryGridTrait;
    use ModuleTrait;

    public string $categoryParamName = 'category';
    public bool $showUrl = false;

    #[Override]
    public function init(): void
    {
        $this->rowAttributes ??= fn (Category $category) => [
            'class' => $category->entryCategory ? 'is-selected' : null,
        ];

        $this->columns ??= [
            $this->statusColumn(),
            $this->typeColumn(),
            $this->getNameColumn(),
            $this->getBranchCountColumn(),
            $this->getEntryCountColumn(),
            $this->updatedAtColumn(),
            $this->buttonsColumn(),
        ];

        // Category counter in submenu needs to be updated when categories are added/removed.
        $this->attributes['hx-select'] ??= 'main';

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
            'class' => ButtonsColumn::class,
            'content' => function (Category $category): array {
                // Categories can always be removed even, if they were not supposed to have entries enabled
                if (!$category->hasEntriesEnabled() && !$category->entryCategory) {
                    return [];
                }

                return [
                    Button::make()
                        ->primary()
                        ->icon($category->entryCategory ? 'ban' : 'star')
                        ->post([
                            $category->entryCategory ? 'delete' : 'create',
                            'entry' => $this->dataProvider->entry->id,
                            'category' => $category->id,
                        ]),
                ];
            }
        ];
    }

    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['category/update', 'id' => $model->id];
    }
}
