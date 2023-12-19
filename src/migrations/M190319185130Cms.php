<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\db\Migration;

class M190319185130Cms extends Migration
{
    use MigrationTrait;
    use ModuleTrait;

    public function safeUp(): void
    {
        $schema = $this->getDb()->getSchema();

        foreach ($this->getLanguages() as $language) {
            if ($language) {
                Yii::$app->language = $language;
            }

            // Entry.
            $this->createTable(Entry::tableName(), [
                'id' => $this->primaryKey()->unsigned(),
                'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(Entry::STATUS_ENABLED),
                'type' => $this->smallInteger()->notNull()->defaultValue(Entry::TYPE_DEFAULT),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'name' => $this->string(250)->notNull(),
                'slug' => $this->string(100)->notNull(),
                'title' => $this->string(250)->notNull(),
                'description' => $this->string(250)->null(),
                'content' => $this->text()->null(),
                'publish_date' => $this->dateTime()->notNull(),
                'section_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'asset_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $entry = Entry::create();
            $this->addI18nColumns(Entry::tableName(), $entry->i18nAttributes);

            foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
                $this->createIndex($attributeName, Entry::tableName(), $attributeName, true);
            }

            $tableName = $schema->getRawTableName(Entry::tableName());
            $this->addForeignKey($tableName . '_updated_by_ibfk', Entry::tableName(), 'updated_by_user_id', User::tableName(), 'id', 'SET NULL');

            // Section.
            $this->createTable(Section::tableName(), [
                'id' => $this->primaryKey()->unsigned(),
                'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(Section::STATUS_ENABLED),
                'type' => $this->smallInteger()->notNull()->defaultValue(Section::TYPE_DEFAULT),
                'entry_id' => $this->integer()->unsigned()->notNull(),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'name' => $this->string(250)->notNull(),
                'slug' => $this->string(100)->null(),
                'content' => $this->text()->null(),
                'asset_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $section = Section::create();
            $this->addI18nColumns(Section::tableName(), $section->i18nAttributes);

            $this->createIndex('entry_id', Section::tableName(), ['entry_id', 'status', 'position']);

            $tableName = $schema->getRawTableName(Section::tableName());
            $this->addForeignKey($tableName . '_entry_id_ibfk', Section::tableName(), 'entry_id', Entry::tableName(), 'id', 'CASCADE');
            $this->addForeignKey($tableName . '_updated_by_ibfk', Section::tableName(), 'updated_by_user_id', User::tableName(), 'id', 'SET NULL');
        }

        $auth = Yii::$app->getAuthManager();
        $admin = $auth->getRole(User::AUTH_ROLE_ADMIN);

        $author = $auth->createRole(Module::AUTH_ROLE_AUTHOR);
        $auth->add($author);

        $auth->addChild($admin, $author);
    }

    public function safeDown(): void
    {
        foreach ($this->getLanguages() as $language) {
            Yii::$app->language = $language;

            $this->dropTable(Section::tableName());
            $this->dropTable(Entry::tableName());
        }

        $auth = Yii::$app->getAuthManager();
        $this->delete($auth->itemTable, ['name' => Module::AUTH_ROLE_AUTHOR]);

        $auth->invalidateCache();
    }

    private function getLanguages(): array
    {
        return static::getModule()->enableI18nTables ? Yii::$app->getI18n()->getLanguages() : [Yii::$app->language];
    }
}
