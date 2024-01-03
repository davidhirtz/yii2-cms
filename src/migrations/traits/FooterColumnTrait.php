<?php

namespace davidhirtz\yii2\cms\migrations\traits;

use davidhirtz\yii2\cms\models\Entry;
use yii\db\Migration;

/**
 * @mixin Migration
 * @noinspection PhpUnused
 */
trait FooterColumnTrait
{
    use I18nTablesTrait;

    protected function addShowInFooterColumn(): void
    {
        $this->i18nTablesCallback(function () {
            $this->addColumn(Entry::tableName(), 'show_in_footer', $this->boolean()
                ->notNull()
                ->defaultValue(false)
                ->after('publish_date'));
        });
    }

    protected function dropShowInFooterColumn(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropColumn(Entry::tableName(), 'show_in_footer');
        });
    }
}
