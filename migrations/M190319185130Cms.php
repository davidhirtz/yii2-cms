<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\db\Migration;

/**
 * Class M190319185130Cms
 * @package davidhirtz\yii2\cms\migrations
 */
class M190319185130Cms extends Migration
{
    use \davidhirtz\yii2\skeleton\db\MigrationTrait;

    /**
     * @return bool|void
     */
    public function safeUp()
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
                'parent_id' => $this->integer()->unsigned()->null(),
                'lft' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'rgt' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'name' => $this->string(250)->notNull(),
                'slug' => $this->string(100)->notNull(),
                'title' => $this->string(250)->notNull(),
                'description' => $this->string(250)->null(),
                'content' => $this->text()->null(),
                'publish_date' => $this->dateTime()->notNull(),
                'section_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'file_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $this->createIndex('parent_id', Entry::tableName(), ['parent_id', 'status']);
            $this->createIndex('slug', Entry::tableName(), $this->getModule()->enabledNestedSlugs ? ['slug', 'parent_id'] : 'slug', true);

            $tableName = $schema->getRawTableName(Entry::tableName());
            $this->addForeignKey($tableName . '_updated_by_user_id_ibfk', Entry::tableName(), 'updated_by_user_id', User::tableName(), 'id', 'SET NULL');
            $this->addForeignKey($tableName . '_parent_id_ibfk', Entry::tableName(), 'parent_id', Entry::tableName(), 'id', 'SET NULL');

            $this->addI18nColumns(Entry::tableName(), (new Entry)->i18nAttributes);

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
                'file_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $this->createIndex('entry_id', Section::tableName(), ['entry_id', 'status', 'position']);
            $this->createIndex('slug', Section::tableName(), $this->getModule()->enabledNestedSlugs ? ['slug', 'parent_id'] : 'slug', true);

            $tableName = $schema->getRawTableName(Section::tableName());
            $this->addForeignKey($tableName . '_updated_by_user_id_ibfk', Section::tableName(), 'updated_by_user_id', User::tableName(), 'id', 'SET NULL');
            $this->addForeignKey($tableName . '_entry_id_ibfk', Section::tableName(), 'entry_id', Entry::tableName(), 'id', 'CASCADE');

            $this->addI18nColumns(Section::tableName(), (new Section)->i18nAttributes);
        }

        $auth = Yii::$app->getAuthManager();
        $admin = $auth->getRole('admin');

        $author = $auth->createRole('author');
        $auth->add($author);

        $auth->addChild($admin, $author);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        foreach ($this->getLanguages() as $language) {
            Yii::$app->language = $language;
            $this->dropTable(Section::tableName());
            $this->dropTable(Entry::tableName());
        }

        $auth = Yii::$app->getAuthManager();
        $this->delete($auth->itemTable, ['name' => 'author']);

        $auth->invalidateCache();
    }

    /**
     * @return array
     */
    private function getLanguages()
    {
        try {
            return $this->getModule()->enableI18nTables ? Yii::$app->getI18n()->getLanguages() : [Yii::$app->language];
        } catch (\Exception $ex) {
            die("WARNING: Module \"cms\" is not properly configured.\n");
        }
    }

    /**
     * @return Module
     */
    private function getModule()
    {
        return Yii::$app->getModule('cms');
    }
}
