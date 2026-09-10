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
        $category = Category::instance();

        foreach ($category->getI18nAttributeNames('slug') as $attributeName) {
            try {
                $this->dropIndex($attributeName, $category::tableName());
            } catch (Exception) {
            }

            $this->createIndex($attributeName, $category::tableName(), $attributeName, true);
        }

        $this->dropSlugIndex();
        $this->createSlugIndex();
    }
}
