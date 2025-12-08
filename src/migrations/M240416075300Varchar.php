<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Migrations\Traits\I18nTablesTrait;
use Hirtz\Cms\models\Asset;
use Hirtz\Cms\models\Category;
use Hirtz\Cms\models\Entry;
use Hirtz\Cms\models\Section;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
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
