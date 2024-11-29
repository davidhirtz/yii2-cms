<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M190909152855Category extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            $schema = $this->getDb()->getSchema();

            // Category
            $this->createTable(Category::tableName(), [
                'id' => $this->primaryKey()->unsigned(),
                'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(Category::STATUS_ENABLED),
                'type' => $this->smallInteger()->notNull()->defaultValue(Category::TYPE_DEFAULT),
                'parent_id' => $this->integer()->unsigned()->null(),
                'lft' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'rgt' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'name' => $this->string()->notNull(),
                'slug' => $this->string(100)->notNull(),
                'title' => $this->string()->null(),
                'description' => $this->string()->null(),
                'content' => $this->text()->null(),
                'entry_count' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->notNull(),
            ], $this->getTableOptions());

            $category = Category::create();
            $this->addI18nColumns(Category::tableName(), $category->i18nAttributes);

            foreach ($category->getI18nAttributeNames('slug') as $attributeName) {
                $this->createIndex($attributeName, Category::tableName(), ['parent_id', $attributeName], true);
            }

            $this->createIndex('parent_id', Category::tableName(), ['parent_id', 'status']);

            $tableName = $schema->getRawTableName(Category::tableName());

            $this->addForeignKey(
                "{$tableName}_parent_id_ibfk",
                Category::tableName(),
                'parent_id',
                Category::tableName(),
                'id',
                'SET NULL'
            );

            $this->addForeignKey(
                "{$tableName}_updated_by_ibfk",
                Category::tableName(),
                'updated_by_user_id',
                User::tableName(),
                'id',
                'SET NULL'
            );

            // EntryCategory
            $this->createTable(EntryCategory::tableName(), [
                'entry_id' => $this->integer()->unsigned(),
                'category_id' => $this->integer()->unsigned(),
                'position' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'updated_by_user_id' => $this->integer()->unsigned()->null(),
                'updated_at' => $this->dateTime(),
            ], $this->getTableOptions());

            $this->addPrimaryKey('entry_id', EntryCategory::tableName(), ['entry_id', 'category_id']);

            $tableName = $schema->getRawTableName(EntryCategory::tableName());

            $this->addForeignKey(
                "{$tableName}_entry_id_ibfk",
                EntryCategory::tableName(),
                'entry_id',
                Entry::tableName(),
                'id',
                'CASCADE'
            );

            $this->addForeignKey(
                "{$tableName}_category_id_ibfk",
                EntryCategory::tableName(),
                'category_id',
                Category::tableName(),
                'id',
                'CASCADE'
            );

            $this->addForeignKey(
                "{$tableName}_updated_by_ibfk",
                EntryCategory::tableName(),
                'updated_by_user_id',
                User::tableName(),
                'id',
                'SET NULL'
            );

            $this->addColumn(Entry::tableName(), 'category_ids', (string)$this->text()
                ->null()
                ->after('publish_date'));
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropColumn(Entry::tableName(), 'category_ids');

            $this->dropTable(EntryCategory::tableName());
            $this->dropTable(Category::tableName());
        });
    }
}
