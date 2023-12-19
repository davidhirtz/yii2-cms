<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\db\I18nActiveQuery;

/**
 * @extends I18nActiveQuery<Category>
 */
class CategoryQuery extends I18nActiveQuery
{
    public $orderBy = ['lft' => SORT_ASC];

    public function selectSiteAttributes(): static
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->attributes(), [
            'updated_by_user_id',
            'created_at',
        ])));
    }

    public function selectSitemapAttributes(): static
    {
        return $this->addSelect($this->prefixColumns([
            'id',
            'status',
            'type',
            'parent_id',
            'lft',
            'rgt',
            ...Category::instance()->getI18nAttributesNames(['slug']),
            'updated_at',
        ]));
    }

    public function matching(?string $search): static
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $tableName = $this->getModelInstance()::tableName();
            $this->andWhere("$tableName.[[name]] LIKE :search", [':search' => "%$search%"]);
        }

        return $this;
    }

    public function whereHasDescendantsEnabled(): static
    {
        return $this;
    }
}
