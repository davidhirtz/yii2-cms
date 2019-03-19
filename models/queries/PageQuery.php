<?php

namespace davidhirtz\yii2\cms\models\queries;

/**
 * Class PageQuery
 * @package davidhirtz\yii2\cms\models\queries
 */
class PageQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
    /**
     * @return bool
     */
    public function isSortedByPosition()
    {
        return $this->orderBy && key($this->orderBy) === 'position';
    }

    /**
     * @param string $search
     * @return $this
     */
    public function matching($search)
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $model = $this->getModelInstance();
            $tableName = $model::tableName();

            $this->andWhere("{$tableName}.[[name]] LIKE :search", [':search' => "%{$search}%"]);
        }

        return $this;
    }
}