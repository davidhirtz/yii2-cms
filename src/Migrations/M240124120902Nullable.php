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

class M240124120902Nullable extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->alterColumn(Category::tableName(), 'title', (string)$this->string(255)
            ->null()
            ->defaultValue(null));

        $this->update(Category::tableName(), ['title' => null], ['title' => '']);

        foreach (['parent_slug', 'title'] as $attribute) {
            $this->alterColumn(Entry::tableName(), $attribute, (string)$this->string(255)
                ->null()
                ->defaultValue(null));

            $this->update(Entry::tableName(), [$attribute => null], [$attribute => '']);
        }
    }
}
