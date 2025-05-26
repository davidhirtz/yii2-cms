<?php

declare(strict_types=1);

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
            $this->addColumn(Entry::tableName(), 'show_in_menu', (string)$this->boolean()
                ->notNull()
                ->defaultValue(false)
                ->after('publish_date'));

            $this->createIndex('show_in_menu', Entry::tableName(), ['show_in_menu', 'status', 'position']);
        });
    }

    protected function dropShowInMenuColumn(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropIndex('show_in_menu', Entry::tableName());
            $this->dropColumn(Entry::tableName(), 'show_in_menu');
        });
    }
}
