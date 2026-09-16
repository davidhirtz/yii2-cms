<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * Makes the section-entry link polymorphic, so a block links entries through the same table. `model_id` points at
 * two tables now, which is why it carries no foreign key of its own and the model layer deletes the rows itself.
 *
 * @noinspection PhpUnused
 */
class M260916100000EntryRelation extends Migration
{
    use MigrationTrait;

    private const string LEGACY_TABLE = '{{%section_entry}}';

    public function safeUp(): void
    {
        $schema = $this->getDb()->getSchema();
        $legacy = $schema->getRawTableName(self::LEGACY_TABLE);

        // The column cannot be renamed while a key still needs it.
        $this->dropForeignKey("{$legacy}_section_id_ibfk", self::LEGACY_TABLE);
        $this->dropForeignKey("{$legacy}_entry_id_ibfk", self::LEGACY_TABLE);
        $this->dropForeignKey("{$legacy}_updated_by_ibfk", self::LEGACY_TABLE);
        $this->dropIndex('section_id', self::LEGACY_TABLE);

        $this->renameTable(self::LEGACY_TABLE, EntryRelation::tableName());
        $this->renameColumn(EntryRelation::tableName(), 'section_id', 'model_id');

        $this->addColumn(EntryRelation::tableName(), 'model_class', (string)$this->string()
            ->notNull()
            ->after('id'));

        $this->update(EntryRelation::tableName(), ['model_class' => Section::class]);

        $this->createIndex('model_class', EntryRelation::tableName(), [
            'model_class',
            'model_id',
            'entry_id',
        ], true);

        $this->addEntryRelationForeignKeys();
    }

    public function safeDown(): void
    {
        $schema = $this->getDb()->getSchema();
        $table = $schema->getRawTableName(EntryRelation::tableName());
        $legacy = $schema->getRawTableName(self::LEGACY_TABLE);

        $this->delete(EntryRelation::tableName(), ['not', ['model_class' => Section::class]]);

        $this->dropForeignKey("{$table}_entry_id_ibfk", EntryRelation::tableName());
        $this->dropForeignKey("{$table}_updated_by_ibfk", EntryRelation::tableName());
        $this->dropIndex('model_class', EntryRelation::tableName());

        $this->dropColumn(EntryRelation::tableName(), 'model_class');
        $this->renameColumn(EntryRelation::tableName(), 'model_id', 'section_id');
        $this->renameTable(EntryRelation::tableName(), self::LEGACY_TABLE);

        $this->createIndex('section_id', self::LEGACY_TABLE, ['section_id', 'entry_id'], true);

        $this->addForeignKey(
            "{$legacy}_section_id_ibfk",
            self::LEGACY_TABLE,
            'section_id',
            Section::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            "{$legacy}_entry_id_ibfk",
            self::LEGACY_TABLE,
            'entry_id',
            Entry::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            "{$legacy}_updated_by_ibfk",
            self::LEGACY_TABLE,
            'updated_by_user_id',
            User::tableName(),
            'id',
            'SET NULL'
        );
    }

    protected function addEntryRelationForeignKeys(): void
    {
        $table = $this->getDb()->getSchema()->getRawTableName(EntryRelation::tableName());

        $this->addForeignKey(
            "{$table}_entry_id_ibfk",
            EntryRelation::tableName(),
            'entry_id',
            Entry::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            "{$table}_updated_by_ibfk",
            EntryRelation::tableName(),
            'updated_by_user_id',
            User::tableName(),
            'id',
            'SET NULL'
        );
    }
}
