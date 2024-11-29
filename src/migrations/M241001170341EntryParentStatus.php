<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Entry;
use yii\db\Expression;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M241001170341EntryParentStatus extends Migration
{
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            $this->addColumn(Entry::tableName(), 'parent_status', (string)$this->tinyInteger()
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
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropColumn(Entry::tableName(), 'parent_status');
        });
    }
}
