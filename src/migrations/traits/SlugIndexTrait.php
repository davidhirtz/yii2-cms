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
        $slugTargetAttribute = $entry->slugTargetAttribute ?? ['slug'];

        foreach ($entry->getI18nAttributeNames('slug') as $language => $indexName) {
            $this->createIndex(
                $indexName,
                Entry::tableName(),
                $entry->getI18nAttributesNames($slugTargetAttribute, [$language]),
                true
            );
        }
    }

    protected function dropSlugIndex(): void
    {
        try {
            foreach (Entry::instance()->getI18nAttributeNames('slug') as $attributeName) {
                $this->dropIndex($attributeName, Entry::tableName());
            }
        } catch (Exception) {
        }
    }
}
