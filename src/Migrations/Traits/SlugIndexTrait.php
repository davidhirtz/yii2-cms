<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations\Traits;

use Hirtz\Cms\Models\Entry;
use Exception;

/**
 * Only used by the historical migrations that created the entry slug columns. Those columns are dropped again by
 * {@see \Hirtz\Cms\Migrations\M260909100000Permalink}, so this must not read anything off the current model.
 */
trait SlugIndexTrait
{
    protected function createSlugIndex(): void
    {
        $schema = $this->getDb()->getSchema()->getTableSchema(Entry::tableName());
        $attributes = array_filter(['slug', 'parent_slug'], fn (string $attribute) => $schema->getColumn($attribute) !== null);

        $this->createIndex('slug', Entry::tableName(), array_values($attributes), true);
    }

    protected function dropSlugIndex(): void
    {
        try {
            $this->dropIndex('slug', Entry::tableName());
        } catch (Exception) {
        }
    }
}
