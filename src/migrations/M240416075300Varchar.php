<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use yii\db\Migration;

class M240416075300Varchar extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            foreach (Category::instance()->getI18nAttributeNames('name') as $attribute) {
                $this->alterColumn(Category::tableName(), $attribute, (string)$this->string()->notNull());
            }

            foreach (Category::instance()->getI18nAttributeNames('description') as $attribute) {
                $this->alterColumn(Category::tableName(), $attribute, (string)$this->string()->null());
            }

            foreach (Entry::instance()->getI18nAttributeNames('name') as $attribute) {
                $this->alterColumn(Entry::tableName(), $attribute, (string)$this->string()->notNull());
            }

            foreach (Entry::instance()->getI18nAttributeNames('description') as $attribute) {
                $this->alterColumn(Entry::tableName(), $attribute, (string)$this->string()->null());
            }

            foreach (Section::instance()->getI18nAttributeNames('name') as $attribute) {
                $this->alterColumn(Section::tableName(), $attribute, (string)$this->string()->null());
            }

            foreach (Asset::instance()->getI18nAttributesNames(['name', 'alt_text', 'link']) as $attribute) {
                $this->alterColumn(Asset::tableName(), $attribute, (string)$this->string()->null());
            }
        });
    }
}
