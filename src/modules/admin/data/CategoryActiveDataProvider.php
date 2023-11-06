<?php

namespace davidhirtz\yii2\cms\modules\admin\data;

use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
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
                'entryCategory' => function (ActiveQuery $query) {
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
    }

    public function getPagination(): Pagination|false
    {
        return false;
    }

    public function getSort(): Sort|false
    {
        return false;
    }
}