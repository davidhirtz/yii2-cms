<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * Adds the `depth` column used by {@see \Hirtz\Skeleton\Models\Traits\NestedTreeTrait} and backfills it from the
 * existing nested set (`lft`/`rgt`), so the level of each category is stored rather than derived on read.
 *
 * @noinspection PhpUnused
 */
class M260907100000Depth extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $db = $this->getDb();
        $schema = $db->getSchema();

        if ($schema->getTableSchema(Category::tableName(), true)->getColumn('depth')) {
            return;
        }

        $this->addColumn(Category::tableName(), 'depth', (string)$this->integer()
            ->unsigned()
            ->notNull()
            ->defaultValue(0)
            ->after('rgt'));

        $table = $db->quoteTableName($schema->getRawTableName(Category::tableName()));

        $this->execute("
            UPDATE $table AS node
            INNER JOIN (
                SELECT n.[[id]] AS [[id]], COUNT(p.[[id]]) AS [[depth]]
                FROM $table AS n
                LEFT JOIN $table AS p ON p.[[lft]] < n.[[lft]] AND p.[[rgt]] > n.[[rgt]]
                GROUP BY n.[[id]]
            ) AS ancestors ON ancestors.[[id]] = node.[[id]]
            SET node.[[depth]] = ancestors.[[depth]]
        ");
    }

    public function safeDown(): void
    {
        $this->dropColumnIfExists(Category::tableName(), 'depth');
    }
}
