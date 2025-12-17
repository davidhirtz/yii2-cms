<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Migrations\Traits\I18nTablesTrait;
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
    use I18nTablesTrait;
    use SlugIndexTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function (): void {
            $category = Category::instance();

            foreach ($category->getI18nAttributeNames('slug') as $attributeName) {
                try {
                    $this->dropIndex($attributeName, $category::tableName());
                } catch (Exception) {
                }

                $this->createIndex($attributeName, $category::tableName(), $category->slugTargetAttribute ?: $attributeName, true);
            }

            $this->dropSlugIndex();
            $this->createSlugIndex();
        });
    }
}
