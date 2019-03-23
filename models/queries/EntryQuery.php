<?php

namespace davidhirtz\yii2\cms\models\queries;

/**
 * Class EntryQuery
 * @package davidhirtz\yii2\cms\models\queries
 */
class EntryQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
    /**
     * @return EntryQuery
     */
    public function enabled(): EntryQuery
    {
        $model = $this->getModelInstance();
        return $this->andWhere([$model::tableName() . '.status' => $model::STATUS_ENABLED]);
    }

    /**
     * @param string $search
     * @return $this
     */
    public function matching($search): EntryQuery
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $tableName = $this->getModelInstance()::tableName();
            $this->andWhere("{$tableName}.[[name]] LIKE :search", [':search' => "%{$search}%"]);
        }

        return $this;
    }
}