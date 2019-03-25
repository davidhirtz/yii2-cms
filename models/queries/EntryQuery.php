<?php

namespace davidhirtz\yii2\cms\models\queries;

/**
 * Class EntryQuery
 * @package davidhirtz\yii2\cms\models\queries
 */
class EntryQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
    /**
     * @return $this
     */
    public function selectSiteAttributes()
    {
        return $this;
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