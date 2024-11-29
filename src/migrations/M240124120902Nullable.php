<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\migrations\traits\I18nTablesTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M240124120902Nullable extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function () {
            foreach (Category::instance()->getI18nAttributeNames('title') as $attribute) {
                $this->alterColumn(Category::instance()->tableName(), $attribute, (string)$this->string(255)
                    ->null()
                    ->defaultValue(null));

                $this->update(Category::instance()->tableName(), [$attribute => null], [$attribute => '']);
            }

            foreach (['parent_slug', 'title'] as $name) {
                foreach (Entry::instance()->getI18nAttributeNames($name) as $attribute) {
                    $this->alterColumn(Entry::instance()->tableName(), $attribute, (string)$this->string(255)
                        ->null()
                        ->defaultValue(null));

                    $this->update(Entry::instance()->tableName(), [$attribute => null], [$attribute => '']);
                }
            }
        });
    }
}
