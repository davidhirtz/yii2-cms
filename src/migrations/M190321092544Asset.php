<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;

use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M190321092544Asset extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            $schema = $this->getDb()->getSchema();

            $this->createTable(Asset::tableName(), [
                'id' => $this->primaryKey()->unsigned(),
                'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(Asset::STATUS_ENABLED),
                'type' => $this->smallInteger()->notNull()->defaultValue(Asset::TYPE_DEFAULT),
                'entry_id' => $this->integer()->unsigned()->notNull(),
                'section_id' => $this->integer()->unsigned()->null(),
                'file_id' => $this->integer()->unsigned()->notNull(),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'name' => $this->string()->null(),
                'content' => $this->text()->null(),
                'alt_text' => $this->string()->null(),
                'link' => $this->string()->null(),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $this->addI18nColumns(Asset::tableName(), Asset::instance()->i18nAttributes);

            $this->createIndex('entry_id', Asset::tableName(), ['entry_id', 'status', 'position']);
            $this->createIndex('section_id', Asset::tableName(), ['section_id', 'position']);

            $tableName = $schema->getRawTableName(Asset::tableName());

            $this->addForeignKey(
                "{$tableName}_entry_id_ibfk",
                Asset::tableName(),
                'entry_id',
                Entry::tableName(),
                'id',
                'CASCADE'
            );

            $this->addForeignKey(
                "{$tableName}_section_id_ibfk",
                Asset::tableName(),
                'section_id',
                Section::tableName(),
                'id',
                'CASCADE'
            );

            $this->addForeignKey(
                "{$tableName}_file_id_ibfk",
                Asset::tableName(),
                'file_id',
                File::tableName(),
                'id',
                'CASCADE'
            );

            $this->addForeignKey(
                "{$tableName}_updated_by_ibfk",
                Asset::tableName(),
                'updated_by_user_id',
                User::tableName(),
                'id',
                'SET NULL'
            );

            $columnName = static::getModule()->enableI18nTables
                ? Yii::$app->getI18n()->getAttributeName('cms_asset_count')
                : 'cms_asset_count';

            $this->addColumn(File::tableName(), $columnName, $this->smallInteger()
                ->notNull()
                ->defaultValue(0)
                ->after('transformation_count'));
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropI18nColumns(File::tableName(), ['cms_asset_count']);
            $this->dropTable(Asset::tableName());
        });
    }
}
