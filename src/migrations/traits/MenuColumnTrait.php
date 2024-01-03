<?php

namespace davidhirtz\yii2\cms\migrations\traits;

use davidhirtz\yii2\cms\models\Entry;
use yii\db\Migration;

/**
 * @mixin Migration
 * @noinspection PhpUnused
 */
trait MenuColumnTrait
{
    use I18nTablesTrait;

    protected function addShowInMenuColumn(): void
    {
        $this->i18nTablesCallback(function () {
            $this->addColumn(Entry::tableName(), 'show_in_menu', $this->boolean()
                ->notNull()
                ->defaultValue(false)
                ->after('publish_date'));
        });
    }

    protected function dropShowInMenuColumn(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropColumn(Entry::tableName(), 'show_in_menu');
        });
    }
}
