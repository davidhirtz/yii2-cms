<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Module;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\I18n\Message;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260914110000AuthItems extends Migration
{
    use MigrationTrait;

    private const array LEGACY_ENTRY = [
        'entryAssetCreate',
        'entryAssetDelete',
        'entryAssetOrder',
        'entryAssetUpdate',
        'entryCategoryUpdate',
        'entryCreate',
        'entryDelete',
        'entryOrder',
        'entryUpdate',
        'sectionAssetCreate',
        'sectionAssetDelete',
        'sectionAssetOrder',
        'sectionAssetUpdate',
        'sectionCreate',
        'sectionDelete',
        'sectionOrder',
        'sectionUpdate',
    ];

    private const array LEGACY_CATEGORY = [
        'categoryCreate',
        'categoryDelete',
        'categoryOrder',
        'categoryUpdate',
    ];

    public function safeUp(): void
    {
        $this->addPermission(Entry::AUTH_ENTRY, $this->getEntryDescription(), Module::AUTH_ROLE_AUTHOR);
        $this->replaceAuthItems(self::LEGACY_ENTRY, Entry::AUTH_ENTRY);

        $this->addPermission(Category::AUTH_CATEGORY, $this->getCategoryDescription(), Module::AUTH_ROLE_AUTHOR);
        $this->replaceAuthItems(self::LEGACY_CATEGORY, Category::AUTH_CATEGORY);
    }

    public function safeDown(): void
    {
        $this->restoreAuthItems(self::LEGACY_CATEGORY, Category::AUTH_CATEGORY, $this->getCategoryDescription());
        $this->restoreAuthItems(self::LEGACY_ENTRY, Entry::AUTH_ENTRY, $this->getEntryDescription());
    }

    private function getEntryDescription(): Message
    {
        return Message::make('cms', 'AUTH_ENTRY_DESCRIPTION');
    }

    private function getCategoryDescription(): Message
    {
        return Message::make('cms', 'AUTH_CATEGORY_DESCRIPTION');
    }
}
