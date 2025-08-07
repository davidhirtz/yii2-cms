<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\migrations\traits;

use davidhirtz\yii2\cms\models\Entry;
use Exception;

trait SlugIndexTrait
{
    protected function createSlugIndex(): void
    {
        $entry = Entry::instance();
        $schema = $this->getDb()->getSchema()->getTableSchema($entry::tableName());
        $slugTargetAttribute = $entry->slugTargetAttribute ?? ['slug'];

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
