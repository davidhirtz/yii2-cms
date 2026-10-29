<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M261029100000MessageKeys extends Migration
{
    use MigrationTrait;

    private const array KEYS = [
        'COMMON_ENTRY_ORDER_CHANGED' => 'ENTRY_ORDER_TRAIL',
        'REORDER_CATEGORIES_CATEGORY_ORDER_CHANGED' => 'CATEGORY_ORDER_TRAIL',
        'REORDER_ENTRY_RELATIONS_LINKED' => 'ENTRY_RELATION_ORDER_TRAIL',
        'REORDER_SECTIONS_SECTION_ORDER_CHANGED' => 'SECTION_ORDER_TRAIL',
    ];

    #[Override]
    public function safeUp(): void
    {
        $this->renameMessageKeys('cms', self::KEYS);
    }

    #[Override]
    public function safeDown(): void
    {
        $this->renameMessageKeys('cms', array_flip(self::KEYS));
    }
}
