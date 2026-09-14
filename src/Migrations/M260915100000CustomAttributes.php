<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * Moves the free text of the CMS models into their `custom_attributes` column. A project that had `content` among
 * the `i18nAttributes` of an entry or a category has to declare the attribute as a translatable custom attribute
 * instead, or the values this migration copied stay unread.
 *
 * @noinspection PhpUnused
 */
class M260915100000CustomAttributes extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->moveColumnsToCustomAttributes(Entry::tableName(), ['content'], Entry::class);
        $this->moveColumnsToCustomAttributes(Category::tableName(), ['content'], Category::class);
        $this->moveColumnsToCustomAttributes(Section::tableName(), ['name', 'slug', 'content'], Section::class);
    }

    public function safeDown(): void
    {
        $this->restoreColumnsFromCustomAttributes(Section::tableName(), [
            'name' => (string)$this->string()->null()->after('position'),
            'slug' => (string)$this->string(Section::SLUG_MAX_LENGTH)->null()->after('name'),
            'content' => (string)$this->text()->null()->after('slug'),
        ], Section::class);

        $this->restoreColumnsFromCustomAttributes(Category::tableName(), [
            'content' => (string)$this->text()->null()->after('description'),
        ], Category::class);

        $this->restoreColumnsFromCustomAttributes(Entry::tableName(), [
            'content' => (string)$this->text()->null()->after('description'),
        ], Entry::class);
    }
}
