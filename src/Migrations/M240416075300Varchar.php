<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

class M240416075300Varchar extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->alterColumn(Category::tableName(), 'name', (string)$this->string()->notNull());
        $this->alterColumn(Category::tableName(), 'description', (string)$this->string()->null());

        $this->alterColumn(Entry::tableName(), 'name', (string)$this->string()->notNull());
        $this->alterColumn(Entry::tableName(), 'description', (string)$this->string()->null());

        $this->alterColumn(Section::tableName(), 'name', (string)$this->string()->null());

        foreach (['name', 'alt_text', 'link'] as $attribute) {
            $this->alterColumn(Asset::tableName(), $attribute, (string)$this->string()->null());
        }
    }
}
