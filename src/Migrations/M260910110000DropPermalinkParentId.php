<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Migrations\Traits\I18nTablesTrait;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * Drops the unused `parent_id` self-reference from the permalink table.
 *
 * A permalink stores its full path in `uri`, so a lookup never walks a tree, and subtree rewrites are driven by the
 * owner's tree ({@see \Hirtz\Cms\Models\Entry::afterSave()} and
 * {@see \Hirtz\Cms\Models\Actions\UpdateDescendantPermalinks}). Nothing ever read the column, and no write kept it
 * current beyond its original backfill.
 *
 * @noinspection PhpUnused
 */
class M260910110000DropPermalinkParentId extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function (): void {
            $this->dropForeignKey($this->getPermalinkForeignKeyName(), Permalink::tableName());
            $this->dropColumn(Permalink::tableName(), 'parent_id');
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function (): void {
            $this->addColumn(Permalink::tableName(), 'parent_id', (string)$this->integer()
                ->unsigned()
                ->null()
                ->after('model_id'));

            $this->addForeignKey(
                $this->getPermalinkForeignKeyName(),
                Permalink::tableName(),
                'parent_id',
                Permalink::tableName(),
                'id',
                'SET NULL'
            );
        });
    }

    protected function getPermalinkForeignKeyName(): string
    {
        return $this->getForeignKeyName(Permalink::tableName(), 'parent_id') . '_ibfk';
    }
}
