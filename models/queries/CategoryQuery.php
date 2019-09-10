<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Category;

/**
 * Class CategoryQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
 * @method Category one($db = null)
 */
class CategoryQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
    /**
     * @var array
     */
    public $orderBy = ['lft' => SORT_ASC];

    /**
     * @return $this
     */
    public function selectSiteAttributes()
    {
        return $this->addSelect(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at']));
    }

    /**
     * @param string $search
     * @return $this
     */
    public function matching($search): CategoryQuery
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $tableName = $this->getModelInstance()::tableName();
            $this->andWhere("{$tableName}.[[name]] LIKE :search", [':search' => "%{$search}%"]);
        }

        return $this;
    }
}