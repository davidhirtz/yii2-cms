<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Section;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * Global sections (monorepo issue #109). `name` is a column rather than a custom attribute because the block
 * select sorts by it and the grid searches it. There is no `position`: a block belongs to nothing, so there is
 * no set for it to be ordered within, and the grid sorts by `updated_at` instead.
 *
 * @noinspection PhpUnused
 */
class M260916110000Block extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        // a fresh install has the block table from the baseline already.
        if ($this->hasTable(Block::tableName())) {
            return;
        }

        $schema = $this->getDb()->getSchema();

        $this->createTable(Block::tableName(), [
            'id' => $this->primaryKey()->unsigned(),
            'status' => $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(Block::STATUS_ENABLED),
            'type' => $this->smallInteger()->notNull()->defaultValue(Block::TYPE_DEFAULT),
            'name' => $this->string(250)->notNull(),
            'custom_attributes' => $this->json()->null(),
            'section_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
            'asset_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
            'entry_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
            'updated_by_user_id' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull(),
        ], $this->getTableOptions());

        $this->createIndex('status', Block::tableName(), ['status', 'name']);

        $blockTable = $schema->getRawTableName(Block::tableName());

        $this->addForeignKey(
            "{$blockTable}_updated_by_ibfk",
            Block::tableName(),
            'updated_by_user_id',
            User::tableName(),
            'id',
            'SET NULL'
        );

        // A deleted block leaves its sections in place and invisible, rather than emptying every entry that used it.
        $this->addColumn(Section::tableName(), 'block_id', (string)$this->integer()
            ->unsigned()
            ->null()
            ->after('entry_id'));

        $sectionTable = $schema->getRawTableName(Section::tableName());

        $this->addForeignKey(
            "{$sectionTable}_block_id_ibfk",
            Section::tableName(),
            'block_id',
            Block::tableName(),
            'id',
            'SET NULL'
        );

        $this->addPermission(
            Block::AUTH_BLOCK,
            Message::make('cms', 'AUTH_BLOCK_DESCRIPTION'),
            User::AUTH_ROLE_ADMIN,
            User::AUTH_ROLE_MANAGER,
        );
    }

    public function safeDown(): void
    {
        $schema = $this->getDb()->getSchema();
        $sectionTable = $schema->getRawTableName(Section::tableName());

        $this->dropForeignKey("{$sectionTable}_block_id_ibfk", Section::tableName());
        $this->dropColumn(Section::tableName(), 'block_id');

        $this->delete(EntryRelation::tableName(), ['model_class' => Block::class]);
        $this->delete(Asset::tableName(), ['model_class' => Block::class]);
        $this->dropTable(Block::tableName());

        $auth = $this->getAuthManager();
        $auth->remove($auth->getPermission(Block::AUTH_BLOCK));
        $auth->invalidateCache();
    }
}
