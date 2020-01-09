<?php

namespace davidhirtz\yii2\cms\modules\admin\data\base;

use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use yii\data\ActiveDataProvider;

/**
 * Class EntryActiveDataProvider.
 * @package davidhirtz\yii2\cms\modules\admin\data\base
 * @see \davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider
 *
 * @property EntryQuery $query
 */
class EntryActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    /**
     * @var Category
     */
    public $category;

    /**
     * @var string
     */
    public $searchString;

    /**
     * @var int
     */
    public $type;

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
        $this->query = Entry::find()->replaceI18nAttributes();

        if ($this->getModule()->defaultEntryOrderBy) {
            $this->query->orderBy($this->getModule()->defaultEntryOrderBy);
        }

        if ($this->type && isset(Entry::getTypes()[$this->type])) {
            if (isset(Entry::getTypes()[$this->type]['orderBy'])) {
                $this->query->orderBy(Entry::getTypes()[$this->type]['orderBy']);
            }

            if (isset(Entry::getTypes()[$this->type]['sort'])) {
                $this->setSort(Entry::getTypes()[$this->type]['sort']);
            }

            $this->query->andWhere(['type' => $this->type]);
        }

        if ($this->category) {
            $this->query->orderBy($this->category->getEntryOrderBy())->innerJoinWith([
                'entryCategory' => function (ActiveQuery $query) {
                    $query->onCondition(['category_id' => $this->category->id]);
                }
            ]);
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }

    /**
     * @inheritDoc
     */
    public function getPagination()
    {
        return !$this->isOrderedByPosition() ? parent::getPagination() : false;
    }

    /**
     * @return bool|\yii\data\Sort
     */
    public function getSort()
    {
        return !$this->isOrderedByPosition() ? parent::getSort() : false;
    }

    /**
     * @return bool
     */
    public function isOrderedByPosition()
    {
        return isset($this->query->orderBy) && in_array(key($this->query->orderBy), [
                EntryCategory::tableName() . '.[[position]]',
                'position',
            ]);
    }
}