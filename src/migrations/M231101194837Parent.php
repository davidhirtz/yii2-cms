<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use Exception;
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
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            $schema = $this->getDb()->getSchema();
            $tableSchema = $schema->getTableSchema(Entry::tableName());

            if (!$tableSchema->getColumn('parent_id')) {
                $this->addColumn(Entry::tableName(), 'parent_id', (string)$this->integer()
                    ->unsigned()
                    ->null()
                    ->after('type'));

                $this->addForeignKey(
                    $schema->getRawTableName(Entry::tableName()) . '_parent_id',
                    Entry::tableName(),
                    'parent_id',
                    Entry::tableName(),
                    'id',
                    'SET NULL'
                );
            }

            if (!$tableSchema->getColumn('path')) {
                $this->addColumn(Entry::tableName(), 'path', (string)$this->string()
                    ->null()
                    ->after('position'));
            }

            if (!$tableSchema->getColumn('entry_count')) {
                $this->addColumn(Entry::tableName(), 'entry_count', (string)$this->integer()
                    ->unsigned()
                    ->notNull()
                    ->defaultValue(0)
                    ->after('category_ids'));
            }

            if (!$tableSchema->getColumn('parent_slug')) {
                $this->addColumn(Entry::tableName(), 'parent_slug', (string)$this->string()
                    ->null()
                    ->after('slug'));

                $entry = Entry::create();

                if ($entry->isI18nAttribute('parent_slug')) {
                    $this->addI18nColumns(Entry::tableName(), ['parent_slug']);
                }

                if ($slugTargetAttribute = $entry->slugTargetAttribute) {
                    try {
                        $this->dropSlugIndex();

                        foreach ($entry->getI18nAttributeNames('slug') as $language => $indexName) {
                            $this->createIndex(
                                $indexName,
                                Entry::tableName(),
                                $entry->getI18nAttributesNames($slugTargetAttribute, [$language]),
                                true
                            );
                        }
                    } catch (Exception) {
                    }
                }
            }
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function () {
            $schema = $this->getDb()->getSchema();
            $entry = Entry::instance();

            try {
                $this->dropSlugIndex();

                foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
                    $this->createIndex(
                        $attributeName,
                        Entry::tableName(),
                        $attributeName
                    );
                }
            } catch (Exception) {
            }

            foreach ($entry->getI18nAttributeNames('parent_slug') as $attributeName) {
                $this->dropColumn(Entry::tableName(), $attributeName);
            }

            $this->dropForeignKey($schema->getRawTableName(Entry::tableName()) . '_parent_id', Entry::tableName());

            $this->dropColumn(Entry::tableName(), 'parent_id');
            $this->dropColumn(Entry::tableName(), 'path');
            $this->dropColumn(Entry::tableName(), 'entry_count');
        });
    }

    /**
     * Wraps drop index in try/catch block.
     */
    protected function dropSlugIndex(): void
    {
        $entry = Entry::instance();

        foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
            $this->dropIndex($attributeName, Entry::tableName());
        }
    }
}
