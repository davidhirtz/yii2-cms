<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Page;
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

            // Page.
            $this->createTable(Page::tableName(), [
                'id' => $this->primaryKey()->unsigned(),
                'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(Page::STATUS_ENABLED),
                'type' => $this->smallInteger()->notNull()->defaultValue(Page::TYPE_DEFAULT),
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
                'media_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'sort_by_publish_date' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $this->createIndex('parent_id', Page::tableName(), ['parent_id', 'status']);
            $this->createIndex('slug', Page::tableName(), $this->getModule()->enabledNestedSlugs ? ['slug', 'parent_id'] : 'slug', true);

            $tableName = $schema->getRawTableName(Page::tableName());
            $this->addForeignKey($tableName . '_updated_by_user_id_ibfk', Page::tableName(), 'updated_by_user_id', User::tableName(), 'id', 'SET NULL');
            $this->addForeignKey($tableName . '_parent_id_ibfk', Page::tableName(), 'parent_id', Page::tableName(), 'id', 'SET NULL');

            $this->addI18nColumns(Page::tableName(), (new Page)->i18nAttributes);

            // Section.
            $this->createTable(Section::tableName(), [
                'id' => $this->primaryKey()->unsigned(),
                'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(Section::STATUS_ENABLED),
                'type' => $this->smallInteger()->notNull()->defaultValue(Section::TYPE_DEFAULT),
                'page_id' => $this->integer()->unsigned()->notNull(),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'name' => $this->string(250)->notNull(),
                'slug' => $this->string(100)->null(),
                'content' => $this->text()->null(),
                'media_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $this->createIndex('page_id', Section::tableName(), ['page_id', 'status', 'position']);
            $this->createIndex('slug', Section::tableName(), $this->getModule()->enabledNestedSlugs ? ['slug', 'parent_id'] : 'slug', true);

            $tableName = $schema->getRawTableName(Section::tableName());
            $this->addForeignKey($tableName . '_updated_by_user_id_ibfk', Section::tableName(), 'updated_by_user_id', User::tableName(), 'id', 'SET NULL');
            $this->addForeignKey($tableName . '_page_id_ibfk', Section::tableName(), 'page_id', Page::tableName(), 'id', 'CASCADE');

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
            $this->dropTable(Page::tableName());
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
