<?php

namespace davidhirtz\yii2\cms\modules\admin\data;

use davidhirtz\yii2\cms\models\queries\EntryQuery;
use yii\data\ActiveDataProvider;

/**
 * Class EntryActiveDataProvider.
 * @package davidhirtz\yii2\cms\modules\admin\data
 *
 * @property EntryQuery $query
 */
class EntryActiveDataProvider extends ActiveDataProvider
{
    /**
     * @inheritdoc
     */
    public function getPagination()
    {
        return !$this->isOrderedByPosition() ? parent::getPagination() : false;
    }

    /**
     * @inheritdoc
     */
    public function getSort()
    {
        return !$this->isOrderedByPosition() ? parent::getSort() : false;
    }

    /**
     * @inheritdoc
     */
    public function setSort($value)
    {
        parent::setSort($value);

        if (!$this->getSort()->defaultOrder) {
            $this->getSort()->defaultOrder = $this->query->orderBy;
        }
    }

    /**
     * @return bool
     */
    public function isOrderedByPosition()
    {
        return key($this->query->orderBy) === 'position';
    }
}