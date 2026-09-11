<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M190321092544Asset extends Migration
{
    use MigrationTrait;

    private const string LEGACY_TABLE = '{{%cms_asset}}';
    private const string FILE_COUNT_COLUMN = 'cms_asset_count';

    public function safeUp(): void
    {
        $schema = $this->getDb()->getSchema();

        $this->createTable(self::LEGACY_TABLE, [
            'id' => $this->primaryKey()->unsigned(),
            'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(3),
            'type' => $this->smallInteger()->notNull()->defaultValue(1),
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

        $this->createIndex('entry_id', self::LEGACY_TABLE, ['entry_id', 'status', 'position']);
        $this->createIndex('section_id', self::LEGACY_TABLE, ['section_id', 'position']);

        $tableName = $schema->getRawTableName(self::LEGACY_TABLE);

        $this->addForeignKey(
            "{$tableName}_entry_id_ibfk",
            self::LEGACY_TABLE,
            'entry_id',
            Entry::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}_section_id_ibfk",
            self::LEGACY_TABLE,
            'section_id',
            Section::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}_file_id_ibfk",
            self::LEGACY_TABLE,
            'file_id',
            File::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}_updated_by_ibfk",
            self::LEGACY_TABLE,
            'updated_by_user_id',
            User::tableName(),
            'id',
            'SET NULL'
        );

        $this->addColumn(File::tableName(), self::FILE_COUNT_COLUMN, (string)$this->smallInteger()
            ->notNull()
            ->defaultValue(0)
            ->after('transformation_count'));
    }

    public function safeDown(): void
    {
        $this->dropColumn(File::tableName(), self::FILE_COUNT_COLUMN);

        $this->dropTable(self::LEGACY_TABLE);
    }
}
