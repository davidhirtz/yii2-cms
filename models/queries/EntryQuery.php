<?php

namespace davidhirtz\yii2\cms\models\queries;

/**
 * Class EntryQuery
 * @package davidhirtz\yii2\cms\models\queries
 */
class EntryQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
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