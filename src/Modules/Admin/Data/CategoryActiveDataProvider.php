<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Data;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Queries\CategoryQuery;
use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\Db\ActiveQuery;
use Override;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\data\Sort;

/**
 * @property CategoryQuery $query
 * @property Category[] $models
 * @method Category[] getModels()
 */
class CategoryActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    public ?Category $category = null;
    public ?Entry $entry = null;
    public ?string $searchString = null;
    public bool $showNestedCategories = true;
    public ?int $type = null;

    #[Override]
    public function init(): void
    {
        $this->query = Category::find();
        $this->initQuery();

        parent::init();
    }

    protected function initQuery(): void
    {
        if ($this->entry) {
            $this->query->joinWith([
                'entryCategory' => function (ActiveQuery $query): void {
                    $query->onCondition(['entry_id' => $this->entry->id]);
                }
            ]);
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
            $this->category = null;
        } elseif ($this->entry) {
            if ($this->showNestedCategories) {
                if ($this->category) {
                    $this->query->andWhere(['parent_id' => $this->category->id]);
                } else {
                    $this->query->andWhere([
                        'or',
                        ['parent_id' => null],
                        EntryCategory::tableName() . '.[[entry_id]] IS NOT NULL',
                    ]);
                }
            }
        } else {
            $this->query->andWhere(['parent_id' => $this->category?->id]);
        }

        if ($this->type) {
            $this->query->andWhere(['type' => $this->type]);
        }
    }

    #[Override]
    public function getPagination(): Pagination|false
    {
        return false;
    }

    #[Override]
    public function getSort(): Sort|false
    {
        return false;
    }
}
