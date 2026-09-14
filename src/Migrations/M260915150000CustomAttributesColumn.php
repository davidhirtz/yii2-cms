<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260915150000CustomAttributesColumn extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->moveCustomAttributesColumn(Entry::tableName(), 'description');
        $this->moveCustomAttributesColumn(Category::tableName(), 'slug');
    }

    public function safeDown(): void
    {
        $this->moveCustomAttributesColumnToEnd(Category::tableName());
        $this->moveCustomAttributesColumnToEnd(Entry::tableName());
    }
}
