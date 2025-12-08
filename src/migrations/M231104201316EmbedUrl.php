<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Migrations\Traits\I18nTablesTrait;
use Hirtz\Cms\models\Asset;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M231104201316EmbedUrl extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            $linkAttributes = Asset::instance()->getI18nAttributeNames('link');

            $this->addColumn(Asset::tableName(), 'embed_url', (string)$this->text()
                ->null()
                ->after(array_pop($linkAttributes)));

            if (in_array('embed_url', Asset::instance()->i18nAttributes, true)) {
                $this->addI18nColumns(Asset::tableName(), ['embed_url']);
            }
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropI18nColumns(Asset::tableName(), ['embed_url']);
        });
    }
}
