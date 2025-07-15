<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\migrations\traits\SlugIndexTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
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
        $this->i18nTablesCallback(function () {
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
