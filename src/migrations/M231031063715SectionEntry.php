<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use yii\db\Migration;

/**
 * Creates the {@see SectionEntry} table if it does not exist due to a previous custom implementation.
 * @since 2.0.0
 *
 * @noinspection PhpUnused
 */
class M231031063715SectionEntry extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            $schema = $this->getDb()->getSchema();

            if ($schema->getTableSchema(SectionEntry::tableName())) {
                return;
            }

            $this->createTable(SectionEntry::tableName(), [
                'id' => $this->primaryKey()->unsigned(),
                'section_id' => $this->integer()->unsigned(),
                'entry_id' => $this->integer()->unsigned(),
                'position' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
            ], $this->getTableOptions());

            $this->createIndex('section_id', SectionEntry::tableName(), ['section_id', 'entry_id'], true);

            $tableName = $schema->getRawTableName(SectionEntry::tableName());

            $this->addForeignKey(
                "{$tableName}_section_id_ibfk",
                SectionEntry::tableName(),
                'section_id',
                Section::tableName(),
                'id',
                'CASCADE'
            );

            $this->addForeignKey(
                "{$tableName}_entry_id_ibfk",
                SectionEntry::tableName(),
                'entry_id',
                Entry::tableName(),
                'id',
                'CASCADE'
            );

            $this->addForeignKey(
                "{$tableName}_updated_by_ibfk",
                SectionEntry::tableName(),
                'updated_by_user_id',
                User::tableName(),
                'id',
                'SET NULL'
            );

            $this->addColumn(Section::tableName(), 'entry_count', $this->smallInteger()
                ->unsigned()
                ->notNull()
                ->defaultValue(0)
                ->after('asset_count'));
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropTable(SectionEntry::tableName());
            $this->dropColumn(Section::tableName(), 'entry_count');
        });

        parent::safeDown();
    }
}
