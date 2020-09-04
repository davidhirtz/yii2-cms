<?php

namespace davidhirtz\yii2\cms\modules\admin\data\base;

use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use yii\data\ActiveDataProvider;

/**
 * Class CategoryActiveDataProvider
 * @package davidhirtz\yii2\cms\modules\admin\data\base
 *
 * @property CategoryQuery $query
 * @property Category[] $models
 * @method Category[] getModels()
 */
class CategoryActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    /**
     * @var Category
     */
    public $category;

    /**
     * @var Entry
     */
    public $entry;

    /**
     * @var string
     */
    public $searchString;

    /**
     * @var bool
     */
    public $showNestedCategories = true;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->query = Category::find();
        $this->initQuery();

        parent::init();
    }

    /**
     * Inits query.
     */
    protected function initQuery()
    {
        if ($this->query->select) {
            $this->query->replaceI18nAttributes();
        }

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
            $this->query->andWhere(['parent_id' => $this->category->id ?? null]);
        }
    }

    /**
     * @inheritDoc
     */
    public function getPagination()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getSort()
    {
        return false;
    }
}