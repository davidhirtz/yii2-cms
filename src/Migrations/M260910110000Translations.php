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
        foreach ($this->getTables() as $table => $modelClass) {
            $this->moveI18nColumnsToTranslations($table, $modelClass);
        }
    }

    public function safeDown(): void
    {
        foreach ($this->getTables() as $table => $modelClass) {
            $this->restoreI18nColumnsFromTranslations($table, $modelClass);
        }

        foreach ($this->getI18nColumns(Category::tableName()) as $column => [$attribute]) {
            if ($attribute === 'slug') {
                $this->createIndex($column, Category::tableName(), $column, true);
            }
        }
    }

    /**
     * @return array<string, class-string>
     */
    protected function getTables(): array
    {
        return [
            Entry::tableName() => Entry::class,
            Section::tableName() => Section::class,
            Category::tableName() => Category::class,
        ];
    }
}
