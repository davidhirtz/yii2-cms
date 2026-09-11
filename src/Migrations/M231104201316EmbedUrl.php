<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Asset;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M231104201316EmbedUrl extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->addColumn(Asset::tableName(), 'embed_url', (string)$this->text()
            ->null()
            ->after('link'));
    }

    public function safeDown(): void
    {
        $this->dropColumn(Asset::tableName(), 'embed_url');
    }
}
