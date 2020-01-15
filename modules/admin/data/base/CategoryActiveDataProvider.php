<?php

namespace davidhirtz\yii2\cms\modules\admin\data\base;

use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use yii\data\ActiveDataProvider;

/**
 * Class CategoryActiveDataProvider.
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
     * @inheritDoc
     */
    public function init()
    {
        $this->initQuery();
        parent::init();
    }

    /**
     * Inits query.
     */
    protected function initQuery()
    {
        $this->query = Category::find()
            ->replaceI18nAttributes();

        if ($this->searchString) {
            $this->query->matching($this->searchString);
            $this->category = null;
        }

        if ($this->entry) {
            $this->query->joinWith([
                'entryCategory' => function (ActiveQuery $query) {
                    $query->onCondition(['entry_id' => $this->entry->id]);
                }
            ]);
        } elseif (!$this->searchString) {
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