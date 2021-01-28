<?php

namespace davidhirtz\yii2\cms\modules\admin\data\base;

use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use yii\data\ActiveDataProvider;

/**
 * Class EntryActiveDataProvider
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
        if (!$this->query) {
            $this->query = Entry::find();
        }

        $this->initQuery();
        parent::init();
    }

    /**
     * Inits query.
     */
    protected function initQuery()
    {
        if (static::getModule()->defaultEntryOrderBy) {
            $this->query->orderBy(static::getModule()->defaultEntryOrderBy);
        }

        $this->query->selectAllColumns()->replaceI18nAttributes();

        if ($type = (Entry::getTypes()[$this->type] ?? false)) {
            if (isset($type['orderBy'])) {
                $this->query->orderBy($type['orderBy']);
            }

            if (isset($type['sort'])) {
                $this->setSort($type['sort']);
            }

            $this->query->andWhere([Entry::tableName() . '.[[type]]' => $this->type]);
        }

        if ($this->category) {
            $this->query->whereCategory($this->category);
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
     * @param array|bool|\yii\data\Sort $value
     */
    public function setSort($value)
    {
        // Try to set default order from query if it's a single order.
        if (!isset($value['defaultOrder']) && is_array($this->query->orderBy) && count($this->query->orderBy) === 1) {
            $value['defaultOrder'] = $this->query->orderBy;
        }

        parent::setSort($value);
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