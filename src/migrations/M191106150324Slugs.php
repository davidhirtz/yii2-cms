<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use Exception;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M191106150324Slugs extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            $category = Category::instance();

            foreach ($category->getI18nAttributeNames('slug') as $attributeName) {
                try {
                    $this->dropIndex($attributeName, Category::tableName());
                    $this->createIndex($attributeName, Category::tableName(), $category->slugTargetAttribute ?: $attributeName, true);
                } catch (Exception) {
                }
            }

            $entry = Entry::instance();

            foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
                try {
                    $this->dropIndex($attributeName, Entry::tableName());
                    $this->createIndex($attributeName, Entry::tableName(), $entry->slugTargetAttribute ?: $attributeName, true);
                } catch (Exception) {
                }
            }
        });
    }
}
