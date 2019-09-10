<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\db\Migration;

/**
 * Class M190909152855Category
 */
class M190909152855Category extends Migration
{
    use ModuleTrait, MigrationTrait;

    public function safeUp()
    {
        $schema = $this->getDb()->getSchema();

        foreach ($this->getLanguages() as $language) {

            if ($language) {
                Yii::$app->language = $language;
            }

            $this->createTable(Category::tableName(), [
                'id' => $this->primaryKey()->unsigned(),
                'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(Category::STATUS_ENABLED),
                'type' => $this->smallInteger()->notNull()->defaultValue(Category::TYPE_DEFAULT),
                'parent_id' => $this->integer()->unsigned()->null(),
                'lft' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'rgt' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'name' => $this->string(250)->notNull(),
                'slug' => $this->string(100)->notNull(),
                'title' => $this->string(250)->notNull(),
                'description' => $this->string(250)->null(),
                'content' => $this->text()->null(),
                'entry_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $tableName = $schema->getRawTableName(Category::tableName());

            $this->createIndex('parent_id', Category::tableName(), ['parent_id', 'status']);
            $this->createIndex('slug', Category::tableName(), ['slug', 'parent_id'], true);

            $this->addForeignKey($tableName . '_parent_id_ibfk', Category::tableName(), 'parent_id', Category::tableName(), 'id', 'SET NULL');
            $this->addForeignKey($tableName . '_updated_by_ibfk', Category::tableName(), 'updated_by_user_id', User::tableName(), 'id', 'SET NULL');

            $this->addI18nColumns(Category::tableName(), (new Category)->i18nAttributes);

            $this->createTable(EntryCategory::tableName(), [
                'entry_id' => $this->integer()->unsigned(),
                'category_id' => $this->integer()->unsigned(),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
            ], $this->getTableOptions());

            $tableName = $schema->getRawTableName(EntryCategory::tableName());

            $this->addPrimaryKey('entry_id', EntryCategory::tableName(), ['entry_id', 'category_id']);

            $this->addForeignKey($tableName . '_entry_id_ibfk', EntryCategory::tableName(), 'entry_id', Entry::tableName(), 'id', 'CASCADE');
            $this->addForeignKey($tableName . '_category_id_ibfk', EntryCategory::tableName(), 'category_id', Category::tableName(), 'id', 'CASCADE');
            $this->addForeignKey($tableName . '_updated_by_ibfk', EntryCategory::tableName(), 'updated_by_user_id', User::tableName(), 'id', 'SET NULL');

            $this->addColumn(Entry::tableName(), 'category_ids', $this->string()->null()->after('publish_date'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        foreach ($this->getLanguages() as $language) {
            Yii::$app->language = $language;

            $this->dropColumn(Entry::tableName(), 'category_ids');

            $this->dropTable(EntryCategory::tableName());
            $this->dropTable(Category::tableName());
        }
    }

    /**
     * @return array
     */
    private function getLanguages()
    {
        return static::getModule()->enableI18nTables ? Yii::$app->getI18n()->getLanguages() : [Yii::$app->language];
    }
}
