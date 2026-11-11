<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M261110100000EntryReference extends Migration
{
    use MigrationTrait;

    #[Override]
    public function safeUp(): void
    {
        $this->createTable('{{%entry_reference}}', [
            'id' => $this->primaryKey()->unsigned(),
            'entry_id' => $this->integer()->unsigned()->notNull(),
            'model_class' => $this->string(255)->notNull(),
            'model_id' => $this->integer()->unsigned()->notNull(),
            'use_count' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0),
            'attribute_counts' => $this->json()->null(),
            'updated_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ], $this->getTableOptions());

        $this->createIndex('model_entry', '{{%entry_reference}}', ['model_class', 'model_id', 'entry_id'], true);
        $this->createIndex('entry_id', '{{%entry_reference}}', 'entry_id');

        $this->addForeignKey(
            'entry_reference_entry_id_ibfk',
            '{{%entry_reference}}',
            'entry_id',
            '{{%entry}}',
            'id',
            'CASCADE',
        );

        $this->addColumn(
            '{{%entry}}',
            'reference_count',
            (string)$this->integer()->unsigned()->notNull()->defaultValue(0)->after('asset_count'),
        );

        $this->addColumn(
            '{{%entry}}',
            'relation_count',
            (string)$this->integer()->unsigned()->notNull()->defaultValue(0)->after('reference_count'),
        );

        $this->execute('UPDATE {{%entry}} SET [[relation_count]] = (SELECT COUNT(*) FROM {{%entry_relation}} WHERE {{%entry_relation}}.[[entry_id]] = {{%entry}}.[[id]])');
    }

    #[Override]
    public function safeDown(): void
    {
        $this->dropColumn('{{%entry}}', 'relation_count');
        $this->dropColumn('{{%entry}}', 'reference_count');
        $this->dropTable('{{%entry_reference}}');
    }
}
