<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
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
    use ModuleTrait;

    public function safeUp(): void
    {
        $schema = $this->getDb()->getSchema();

        foreach ($this->getLanguages() as $language) {
            Yii::$app->language = $language;

            if ($schema->getTableSchema(SectionEntry::tableName())) {
                continue;
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

            $this->addForeignKey("{$tableName}_entry_id_ibfk",
                SectionEntry::tableName(),
                'entry_id',
                Entry::tableName(),
                'id',
                'CASCADE'
            );

            $this->addForeignKey("{$tableName}_updated_by_ibfk",
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
        }

        parent::safeUp();
    }

    public function safeDown(): void
    {
        foreach ($this->getLanguages() as $language) {
            Yii::$app->language = $language;

            $this->dropTable(SectionEntry::tableName());
            $this->dropColumn(Section::tableName(), 'entry_count');
        }

        parent::safeDown();
    }

    private function getLanguages(): array
    {
        return static::getModule()->enableI18nTables ? Yii::$app->getI18n()->getLanguages() : [Yii::$app->language];
    }
}