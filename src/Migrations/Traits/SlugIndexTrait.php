<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations\Traits;

use Hirtz\Cms\Models\Entry;
use Exception;

/**
 * Only used by the historical migrations that created the entry slug columns. Those columns are dropped again by
 * {@see \Hirtz\Cms\Migrations\M260909100000Permalink}, so this must not read anything off the current model beyond
 * its I18N attribute names.
 */
trait SlugIndexTrait
{
    protected function createSlugIndex(): void
    {
        $entry = Entry::instance();
        $schema = $this->getDb()->getSchema()->getTableSchema($entry::tableName());
        $slugTargetAttribute = ['slug', 'parent_slug'];

        foreach ($entry->getI18nAttributeNames('slug') as $language => $indexName) {
            $attributes = $entry->getI18nAttributesNames($slugTargetAttribute, [$language]);
            $attributes = array_filter($attributes, fn ($attribute) => $schema->getColumn($attribute) !== null);

            $this->createIndex(
                $indexName,
                $entry::tableName(),
                $attributes,
                true
            );
        }
    }

    protected function dropSlugIndex(): void
    {
        try {
            $entry = Entry::instance();

            foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
                $this->dropIndex($attributeName, $entry::tableName());
            }
        } catch (Exception) {
        }
    }
}
