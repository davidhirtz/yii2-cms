<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M231104201316EmbedUrl extends Migration
{
    use MigrationTrait;

    private const string LEGACY_TABLE = '{{%cms_asset}}';

    public function safeUp(): void
    {
        $this->addColumn(self::LEGACY_TABLE, 'embed_url', (string)$this->text()
            ->null()
            ->after('link'));
    }

    public function safeDown(): void
    {
        $this->dropColumn(self::LEGACY_TABLE, 'embed_url');
    }
}
