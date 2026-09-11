<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260911100000CustomAttributes extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        foreach ($this->getTableNames() as $table) {
            $this->addCustomAttributesColumn($table);
        }
    }

    public function safeDown(): void
    {
        foreach ($this->getTableNames() as $table) {
            $this->dropCustomAttributesColumn($table);
        }
    }

    /**
     * @return list<string>
     */
    protected function getTableNames(): array
    {
        return [
            Entry::tableName(),
            Section::tableName(),
            Asset::tableName(),
            Category::tableName(),
        ];
    }
}
