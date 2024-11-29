<?php

declare(strict_types=1);

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

            $schema = $this->getDb()->getSchema();

            if ($schema->getTableSchema(Entry::tableName())->getColumn('show_in_menu')) {
                $this->dropIndex('show_in_menu', Entry::tableName());

                $this->createIndex('show_in_menu', Entry::tableName(), [
                    'show_in_menu',
                    'show_in_footer',
                    'status',
                    'position',
                ]);
            }
        });
    }

    protected function dropShowInFooterColumn(): void
    {
        $this->i18nTablesCallback(function () {
            $this->dropColumn(Entry::tableName(), 'show_in_footer');
        });
    }
}
