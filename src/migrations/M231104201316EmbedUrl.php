<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\skeleton\db\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M231104201316EmbedUrl extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $linkAttributes = Asset::instance()->getI18nAttributeNames('link');

        $this->addColumn(Asset::tableName(), 'embed_url', $this->string()
            ->null()
            ->after(array_pop($linkAttributes)));

        $this->addI18nColumns(Asset::tableName(), ['embed_url']);
    }

    public function safeDown(): void
    {
        $this->dropI18nColumns(Asset::tableName(), ['embed_url']);
    }
}