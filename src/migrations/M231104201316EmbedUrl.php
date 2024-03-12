<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
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

            $this->addColumn(Asset::tableName(), 'embed_url', $this->text()
                ->null()
                ->after(array_pop($linkAttributes)));

            if (in_array('embed_url', Asset::instance()->i18nAttributes)) {
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
