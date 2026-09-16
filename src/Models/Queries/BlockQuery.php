<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Block;
use Hirtz\Skeleton\Db\I18nActiveQuery;

/**
 * @template T of Block
 * @template-extends I18nActiveQuery<Block>
 */
class BlockQuery extends I18nActiveQuery
{
    public function matching(?string $search): static
    {
        if ($search = $this->sanitizeSearchString($search)) {
            $tableName = $this->getModelInstance()::tableName();
            $this->andWhere("$tableName.[[name]] LIKE :search", [':search' => "%$search%"]);
        }

        return $this;
    }

    /**
     * Override this method to select only the attributes needed for frontend display.
     */
    public function selectSiteAttributes(): static
    {
        return $this->addSelect($this->prefixColumns(array_diff($this->getModelInstance()->getColumnAttributes(), [
            'updated_by_user_id',
            'updated_at',
            'created_at',
        ])));
    }
}
