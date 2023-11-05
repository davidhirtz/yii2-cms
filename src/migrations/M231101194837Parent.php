<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use Exception;
use Yii;
use yii\db\Migration;

/**
 * This migration is used to add the parent_id functionality to the entry table. Previously, this functionally was added
 * in an extra package `davidhirtz/yii2-cms-parent`. Since v2.0, this package is deprecated and the functionality was
 * moved to the core package.
 *
 * @since v2.0.0
 * @noinspection PhpUnused
 */
class M231101194837Parent extends Migration
{
    use MigrationTrait;
    use ModuleTrait;

    public function safeUp(): void
    {
        $schema = $this->getDb()->getSchema();

        foreach ($this->getLanguages() as $language) {
            Yii::$app->language = $language;

            $tableSchema = $schema->getTableSchema(Entry::tableName());

            if (!$tableSchema->getColumn('parent_id')) {
                $this->addColumn(Entry::tableName(), 'parent_id', $this->integer()
                    ->unsigned()
                    ->null()
                    ->after('type'));

                $this->addForeignKey(
                    $schema->getRawTableName(Entry::tableName()) . '_parent_id',
                    Entry::tableName(),
                    'parent_id', Entry::tableName(),
                    'id',
                    'SET NULL'
                );
            }

            if (!$tableSchema->getColumn('path')) {
                $this->addColumn(Entry::tableName(), 'path', $this->string()
                    ->null()
                    ->after('position'));
            }

            if (!$tableSchema->getColumn('entry_count')) {
                $this->addColumn(Entry::tableName(), 'entry_count', $this->integer()
                    ->unsigned()
                    ->notNull()
                    ->defaultValue(0)
                    ->after('category_ids'));
            }

            if (!$tableSchema->getColumn('parent_slug')) {
                $this->addColumn(Entry::tableName(), 'parent_slug', $this->string()
                    ->notNull()
                    ->defaultValue('')
                    ->after('slug'));

                $entry = Entry::create();

                if ($entry->isI18nAttribute('parent_slug')) {
                    $this->addI18nColumns(Entry::tableName(), ['parent_slug']);
                }

                if ($slugTargetAttribute = $entry->slugTargetAttribute) {
                    $this->dropSlugIndex();

                    foreach ($entry->getI18nAttributeNames('slug') as $language => $indexName) {
                        $this->createIndex(
                            $indexName,
                            Entry::tableName(),
                            $entry->getI18nAttributesNames($slugTargetAttribute, [$language]),
                            true
                        );
                    }
                }
            }
        }
    }

    public function safeDown(): void
    {
        $schema = $this->getDb()->getSchema();

        foreach ($this->getLanguages() as $language) {
            Yii::$app->language = $language;
            $entry = Entry::instance();

            $this->dropSlugIndex();

            foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
                $this->createIndex(
                    $attributeName,
                    Entry::tableName(),
                    $attributeName
                );
            }

            foreach ($entry->getI18nAttributeNames('parent_slug') as $attributeName) {
                $this->dropColumn(Entry::tableName(), $attributeName);
            }

            $this->dropForeignKey($schema->getRawTableName(Entry::tableName()) . '_parent_id', Entry::tableName());

            $this->dropColumn(Entry::tableName(), 'parent_id');
            $this->dropColumn(Entry::tableName(), 'path');
            $this->dropColumn(Entry::tableName(), 'entry_count');
        }
    }

    /**
     * Wraps drop index in try/catch block.
     */
    protected function dropSlugIndex(): void
    {
        $entry = Entry::instance();

        foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
            try {
                $this->dropIndex($attributeName, Entry::tableName());
            } catch (Exception) {
            }
        }
    }

    private function getLanguages(): array
    {
        return static::getModule()->enableI18nTables ? Yii::$app->getI18n()->getLanguages() : [Yii::$app->language];
    }
}