<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * The migration that creates the table keeps its original column, so this must stay a no-op on a fresh install.
 *
 * @noinspection PhpUnused
 */
class M260912091000PermalinkModelClass extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        if (!$this->hasColumn(Permalink::tableName(), 'model')) {
            return;
        }

        $this->dropIndexIfExists('model', Permalink::tableName());
        $this->renameColumn(Permalink::tableName(), 'model', 'model_class');
        $this->createIndex('model_class', Permalink::tableName(), ['model_class', 'model_id', 'language'], true);
    }

    public function safeDown(): void
    {
        if (!$this->hasColumn(Permalink::tableName(), 'model_class')) {
            return;
        }

        $this->dropIndexIfExists('model_class', Permalink::tableName());
        $this->renameColumn(Permalink::tableName(), 'model_class', 'model');
        $this->createIndex('model', Permalink::tableName(), ['model', 'model_id', 'language'], true);
    }
}
