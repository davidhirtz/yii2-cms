<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * Creates the `section_entry` table if it does not exist due to a previous custom implementation. The table is
 * renamed by {@see M260916100000EntryRelation}, so the name is spelled out rather than read off the model.
 *
 * @since 2.0.0
 *
 * @noinspection PhpUnused
 */
class M231031063715SectionEntry extends Migration
{
    use MigrationTrait;

    private const string TABLE = '{{%section_entry}}';

    public function safeUp(): void
    {
        $schema = $this->getDb()->getSchema();

        if ($schema->getTableSchema(self::TABLE)) {
            return;
        }

        $this->createTable(self::TABLE, [
            'id' => $this->primaryKey()->unsigned(),
            'section_id' => $this->integer()->unsigned(),
            'entry_id' => $this->integer()->unsigned(),
            'position' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
            'updated_by_user_id' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->dateTime(),
        ], $this->getTableOptions());

        $this->createIndex('section_id', self::TABLE, ['section_id', 'entry_id'], true);

        $tableName = $schema->getRawTableName(self::TABLE);

        $this->addForeignKey(
            "{$tableName}_section_id_ibfk",
            self::TABLE,
            'section_id',
            Section::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}_entry_id_ibfk",
            self::TABLE,
            'entry_id',
            Entry::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}_updated_by_ibfk",
            self::TABLE,
            'updated_by_user_id',
            User::tableName(),
            'id',
            'SET NULL'
        );

        $this->addColumn(Section::tableName(), 'entry_count', (string)$this->smallInteger()
            ->unsigned()
            ->notNull()
            ->defaultValue(0)
            ->after('asset_count'));
    }

    public function safeDown(): void
    {
        $this->dropTable(self::TABLE);
        $this->dropColumn(Section::tableName(), 'entry_count');

        parent::safeDown();
    }
}
