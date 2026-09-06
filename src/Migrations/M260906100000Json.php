<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;
use yii\db\Query;

/**
 * Replaces the legacy comma-separated `path` and `category_ids` columns with native JSON arrays.
 *
 * @noinspection PhpUnused
 */
class M260906100000Json extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $tableName = Entry::tableName();
        $db = $this->getDb();

        foreach (['path', 'category_ids'] as $attribute) {
            $this->update($tableName, [$attribute => null], [$attribute => '']);

            $rows = (new Query())
                ->select(['id', $attribute])
                ->from($tableName)
                ->where(['not', [$attribute => null]])
                ->all($db);

            foreach ($rows as $row) {
                $ids = array_map(intval(...), explode(',', (string)$row[$attribute]));

                $db->createCommand()
                    ->update($tableName, [$attribute => json_encode($ids)], ['id' => $row['id']])
                    ->execute();
            }
        }

        $this->alterColumn($tableName, 'path', (string)$this->json()->null());
        $this->alterColumn($tableName, 'category_ids', (string)$this->json()->null());
    }

    public function safeDown(): void
    {
        $tableName = Entry::tableName();
        $db = $this->getDb();

        $this->alterColumn($tableName, 'path', (string)$this->string()->null());
        $this->alterColumn($tableName, 'category_ids', (string)$this->text()->null());

        foreach (['path', 'category_ids'] as $attribute) {
            $rows = (new Query())
                ->select(['id', $attribute])
                ->from($tableName)
                ->where(['not', [$attribute => null]])
                ->all($db);

            foreach ($rows as $row) {
                $ids = json_decode((string)$row[$attribute], true);

                $db->createCommand()
                    ->update($tableName, [$attribute => $ids ? implode(',', $ids) : null], ['id' => $row['id']])
                    ->execute();
            }
        }
    }
}
