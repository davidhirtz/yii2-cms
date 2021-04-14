<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

/**
 * Class CategoryQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
 * @method Category[] all($db = null)
 * @method Category[] each($batchSize = 100, $db = null)
 * @method Category one($db = null)
 */
class CategoryQuery extends ActiveQuery
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
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at'])));
    }

    /**
     * @return $this
     */
    public function selectSitemapAttributes()
    {
        return $this->addSelect($this->prefixColumns(['id', 'status', 'type', 'parent_id', 'lft', 'rgt', 'slug', 'updated_at']));
    }

    /**
     * @param string $search
     * @return $this
     */
    public function matching($search)
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $tableName = $this->getModelInstance()::tableName();
            $this->andWhere("{$tableName}.[[name]] LIKE :search", [':search' => "%{$search}%"]);
        }

        return $this;
    }
}