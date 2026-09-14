<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * A category has no URL of its own, so its meta title and description are read where it is rendered rather than
 * queried; they follow the content into `custom_attributes`. The entry keeps both as columns.
 *
 * @noinspection PhpUnused
 */
class M260915120000CategoryMeta extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->moveColumnsToCustomAttributes(Category::tableName(), ['title', 'description'], Category::class);
    }

    public function safeDown(): void
    {
        $this->restoreColumnsFromCustomAttributes(Category::tableName(), [
            'title' => (string)$this->string()->null()->after('slug'),
            'description' => (string)$this->string()->null()->after('title'),
        ], Category::class);
    }
}
