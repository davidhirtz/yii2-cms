<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use yii\db\Expression;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M241001170341EntryParentStatus extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->addColumn(Entry::tableName(), 'parent_status', $this->tinyInteger()
            ->notNull()
            ->defaultValue(Entry::STATUS_DEFAULT)
            ->after('status'));

        $query = Entry::find()
            ->where(['>', 'entry_count', 0])
            ->andWhere(['<', 'status', Entry::STATUS_ENABLED])
            ->orderBy(new Expression('[[path]] IS NULL, [[path]]'));

        /** @var Entry $parent */
        foreach ($query->each() as $parent) {
            /** @var Entry $entry */
            foreach ($parent->getChildren() as $entry) {
                $entry->parent_status = min($parent->status, $parent->parent_status);
                $entry->update();
            }
        }
    }

    public function safeDown(): void
    {
        $this->dropColumn(Entry::tableName(), 'parent_status');
    }
}
