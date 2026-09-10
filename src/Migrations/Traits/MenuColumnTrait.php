<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations\Traits;

use Hirtz\Cms\Models\Entry;
use yii\db\Migration;

/**
 * @mixin Migration
 * @noinspection PhpUnused
 */
trait MenuColumnTrait
{
    protected function addShowInMenuColumn(): void
    {
        $this->addColumn(Entry::tableName(), 'show_in_menu', (string)$this->boolean()
            ->notNull()
            ->defaultValue(false)
            ->after('publish_date'));

        $this->createIndex('show_in_menu', Entry::tableName(), ['show_in_menu', 'status', 'position']);
    }

    protected function dropShowInMenuColumn(): void
    {
        $this->dropIndex('show_in_menu', Entry::tableName());
        $this->dropColumn(Entry::tableName(), 'show_in_menu');
    }
}
