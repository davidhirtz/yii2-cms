<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Migrations\Traits\SlugIndexTrait;
use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Exception;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M191106150324Slugs extends Migration
{
    use MigrationTrait;
    use SlugIndexTrait;

    public function safeUp(): void
    {
        try {
            $this->dropIndex('slug', Category::tableName());
        } catch (Exception) {
        }

        $this->createIndex('slug', Category::tableName(), 'slug', true);

        $this->dropSlugIndex();
        $this->createSlugIndex();
    }
}
