<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\db\ActiveQuery;

/**
 * @method Category[] all($db = null)
 * @method Category[] each($batchSize = 100, $db = null)
 * @method Category one($db = null)
 */
class CategoryQuery extends ActiveQuery
{
    public $orderBy = ['lft' => SORT_ASC];

    public function selectSiteAttributes(): static
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at'])));
    }

    public function selectSitemapAttributes(): static
    {
        return $this->addSelect($this->prefixColumns(array_merge(
            ['id', 'status', 'type', 'parent_id', 'lft', 'rgt', 'updated_at'],
            Category::instance()->getI18nAttributesNames(['slug'])
        )));
    }

    public function matching(?string $search): static
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $tableName = $this->getModelInstance()::tableName();
            $this->andWhere("{$tableName}.[[name]] LIKE :search", [':search' => "%{$search}%"]);
        }

        return $this;
    }

    public function whereHasDescendantsEnabled(): static
    {
        return $this;
    }
}