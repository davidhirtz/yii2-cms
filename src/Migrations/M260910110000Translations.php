<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Translation;
use yii\db\Migration;

/**
 * Moves the translated attributes of the CMS models from their `_xx` columns into {@see Translation} records.
 *
 * The asset is not among them: its model is gone and `M260912110000Assets` reads whichever of the two shapes it
 * finds on `cms_asset`.
 *
 * @noinspection PhpUnused
 */
class M260910110000Translations extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        foreach ($this->getModels() as $model) {
            $this->moveI18nColumnsToTranslations($model);
        }
    }

    public function safeDown(): void
    {
        foreach ($this->getModels() as $model) {
            $this->restoreI18nColumnsFromTranslations($model);
        }

        $category = Category::create();

        foreach ($category->getI18nAttributeNames('slug') as $attributeName) {
            if ($attributeName !== 'slug') {
                $this->createIndex($attributeName, $category::tableName(), $attributeName, true);
            }
        }
    }

    /**
     * @return list<Category|Entry|Section>
     */
    protected function getModels(): array
    {
        return [
            Entry::create(),
            Section::create(),
            Category::create(),
        ];
    }
}
