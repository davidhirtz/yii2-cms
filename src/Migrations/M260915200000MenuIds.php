<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;
use yii\db\Query;

/**
 * Replaces the optional `show_in_menu` and `show_in_footer` columns with a JSON list of menu values. A v2
 * installation that carried either is upgraded in place: menu `1` is what `show_in_menu` becomes and menu `2`
 * what `show_in_footer` becomes, so declaring two menus under those values keeps every entry where it was.
 *
 * The two columns were a project's own, added by migration traits the bundle no longer ships, so `safeDown()`
 * drops the menus rather than rebuilding columns a fresh install never had.
 *
 * @noinspection PhpUnused
 */
class M260915200000MenuIds extends Migration
{
    use MigrationTrait;

    /**
     * @var array<string, int> the legacy column and the menu value its entries are moved to
     */
    protected array $legacyColumns = [
        'show_in_menu' => 1,
        'show_in_footer' => 2,
    ];

    public function safeUp(): void
    {
        $tableName = Entry::tableName();

        $this->addColumn($tableName, 'menu_ids', (string)$this->json()
            ->null()
            ->after('publish_date'));

        $columns = $this->getLegacyColumns();

        if ($columns) {
            $this->moveLegacyColumns($columns);
        }

        foreach (array_keys($columns) as $column) {
            $this->dropIndexesContainingColumn($tableName, $column);
            $this->dropColumn($tableName, $column);
        }
    }

    public function safeDown(): void
    {
        $this->dropColumn(Entry::tableName(), 'menu_ids');
    }

    /**
     * @return array<string, int>
     */
    protected function getLegacyColumns(): array
    {
        $schema = $this->getDb()->getSchema()->getTableSchema(Entry::tableName(), true);

        return array_filter(
            $this->legacyColumns,
            fn (string $column) => (bool)$schema?->getColumn($column),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param array<string, int> $columns
     */
    protected function moveLegacyColumns(array $columns): void
    {
        $tableName = Entry::tableName();
        $db = $this->getDb();

        $rows = (new Query())
            ->select(['id', ...array_keys($columns)])
            ->from($tableName)
            ->where(['or', ...array_map(fn (string $column) => ['not', [$column => 0]], array_keys($columns))])
            ->all($db);

        foreach ($rows as $row) {
            $menuIds = [];

            foreach ($columns as $column => $menuId) {
                if ($row[$column]) {
                    $menuIds[] = $menuId;
                }
            }

            $db->createCommand()
                ->update($tableName, ['menu_ids' => $menuIds], ['id' => $row['id']])
                ->execute();
        }
    }
}
