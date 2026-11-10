<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use yii\db\Expression;

/**
 * @extends I18nActiveQuery<Category>
 */
class CategoryQuery extends I18nActiveQuery
{
    /**
     * @var array<string, int>
     */
    public $orderBy = ['lft' => SORT_ASC];

    /**
     * Enabled categories without entries and without subcategories, which could hold the entries instead.
     */
    public function whereUnused(): static
    {
        $modelClass = $this->modelClass;
        $table = $modelClass::tableName();

        $children = $modelClass::find()
            ->select(new Expression('1'))
            ->from(['child' => $table])
            ->where("[[child]].[[parent_id]] = $table.[[id]]");

        return $this->andWhere(['>=', "$table.[[status]]", $modelClass::STATUS_ENABLED])
            ->andWhere(["$table.[[entry_count]]" => 0])
            ->andWhere(['not exists', $children]);
    }

    public function selectSiteAttributes(): static
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->getColumnAttributes(), [
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
            'slug',
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
