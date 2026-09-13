<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Module;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Yii;
use yii\db\Migration;

/**
 * The permission names and descriptions this creates are hardcoded: `M2609141[0-6]0000AuthItems` collapses them
 * into one permission per model, so neither the constants nor the message keys exist any more.
 *
 * @noinspection PhpUnused
 */

class M200929203300Rbac extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $auth = Yii::$app->getAuthManager();
        $author = $auth->getRole(Module::AUTH_ROLE_AUTHOR);

        // Category
        $categoryUpdate = $auth->createPermission('categoryUpdate');
        $categoryUpdate->description = 'Update categories';
        $auth->add($categoryUpdate);

        $auth->addChild($author, $categoryUpdate);

        $categoryCreate = $auth->createPermission('categoryCreate');
        $categoryCreate->description = 'Create new categories';
        $auth->add($categoryCreate);

        $auth->addChild($categoryCreate, $categoryUpdate);
        $auth->addChild($author, $categoryCreate);

        $categoryDelete = $auth->createPermission('categoryDelete');
        $categoryDelete->description = 'Delete categories';
        $auth->add($categoryDelete);

        $auth->addChild($categoryDelete, $categoryUpdate);
        $auth->addChild($author, $categoryDelete);

        $categoryOrder = $auth->createPermission('categoryOrder');
        $categoryOrder->description = 'Change category order';
        $auth->add($categoryOrder);

        $auth->addChild($categoryOrder, $categoryUpdate);
        $auth->addChild($author, $categoryOrder);

        // Entry
        $entryUpdate = $auth->createPermission('entryUpdate');
        $entryUpdate->description = 'Update entries';
        $auth->add($entryUpdate);

        $auth->addChild($author, $entryUpdate);

        $entryCreate = $auth->createPermission('entryCreate');
        $entryCreate->description = 'Create new entries';
        $auth->add($entryCreate);

        $auth->addChild($entryCreate, $entryUpdate);
        $auth->addChild($author, $entryCreate);

        $entryDelete = $auth->createPermission('entryDelete');
        $entryDelete->description = 'Delete entries';
        $auth->add($entryDelete);

        $auth->addChild($entryDelete, $entryUpdate);
        $auth->addChild($author, $entryDelete);

        $entryOrder = $auth->createPermission('entryOrder');
        $entryOrder->description = 'Change entry order';
        $auth->add($entryOrder);

        $auth->addChild($entryOrder, $entryUpdate);
        $auth->addChild($author, $entryOrder);

        // EntryCategory
        $entryCategoryUpdate = $auth->createPermission('entryCategoryUpdate');
        $entryCategoryUpdate->description = 'Update entry categories';
        $auth->add($entryCategoryUpdate);

        $auth->addChild($entryCategoryUpdate, $entryUpdate);
        $auth->addChild($author, $entryCategoryUpdate);

        // Section
        $sectionUpdate = $auth->createPermission('sectionUpdate');
        $sectionUpdate->description = 'Update sections';
        $auth->add($sectionUpdate);

        $auth->addChild($sectionUpdate, $entryUpdate);
        $auth->addChild($author, $sectionUpdate);

        $sectionCreate = $auth->createPermission('sectionCreate');
        $sectionCreate->description = 'Create new sections';
        $auth->add($sectionCreate);

        $auth->addChild($sectionCreate, $entryUpdate);
        $auth->addChild($sectionCreate, $sectionUpdate);
        $auth->addChild($author, $sectionCreate);

        $sectionDelete = $auth->createPermission('sectionDelete');
        $sectionDelete->description = 'Delete sections';
        $auth->add($sectionDelete);

        $auth->addChild($sectionDelete, $entryUpdate);
        $auth->addChild($sectionDelete, $sectionUpdate);
        $auth->addChild($author, $sectionDelete);

        $sectionOrder = $auth->createPermission('sectionOrder');
        $sectionOrder->description = 'Change section order';
        $auth->add($sectionOrder);

        $auth->addChild($sectionOrder, $entryUpdate);
        $auth->addChild($sectionOrder, $sectionUpdate);
        $auth->addChild($author, $sectionOrder);

        // EntryAsset
        $entryAssetUpdate = $auth->createPermission('entryAssetUpdate');
        $entryAssetUpdate->description = 'Update entry assets';
        $auth->add($entryAssetUpdate);

        $auth->addChild($entryAssetUpdate, $entryUpdate);
        $auth->addChild($author, $entryAssetUpdate);

        $entryAssetCreate = $auth->createPermission('entryAssetCreate');
        $entryAssetCreate->description = 'Create new entry assets';
        $auth->add($entryAssetCreate);

        $auth->addChild($entryAssetCreate, $entryUpdate);
        $auth->addChild($entryAssetCreate, $entryAssetUpdate);
        $auth->addChild($author, $entryAssetCreate);

        $entryAssetDelete = $auth->createPermission('entryAssetDelete');
        $entryAssetDelete->description = 'Delete entry assets';
        $auth->add($entryAssetDelete);

        $auth->addChild($entryAssetDelete, $entryUpdate);
        $auth->addChild($entryAssetDelete, $entryAssetUpdate);
        $auth->addChild($author, $entryAssetDelete);

        $entryAssetOrder = $auth->createPermission('entryAssetOrder');
        $entryAssetOrder->description = 'Change entry asset order';
        $auth->add($entryAssetOrder);

        $auth->addChild($entryAssetOrder, $entryUpdate);
        $auth->addChild($entryAssetOrder, $entryAssetUpdate);
        $auth->addChild($author, $entryAssetOrder);

        // SectionAsset
        $sectionAssetUpdate = $auth->createPermission('sectionAssetUpdate');
        $sectionAssetUpdate->description = 'Update section assets';
        $auth->add($sectionAssetUpdate);

        $auth->addChild($sectionAssetUpdate, $sectionUpdate);
        $auth->addChild($author, $sectionAssetUpdate);

        $sectionAssetCreate = $auth->createPermission('sectionAssetCreate');
        $sectionAssetCreate->description = 'Create new section assets';
        $auth->add($sectionAssetCreate);

        $auth->addChild($sectionAssetCreate, $sectionUpdate);
        $auth->addChild($sectionAssetCreate, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetCreate);

        $sectionAssetDelete = $auth->createPermission('sectionAssetDelete');
        $sectionAssetDelete->description = 'Delete section assets';
        $auth->add($sectionAssetDelete);

        $auth->addChild($sectionAssetDelete, $sectionUpdate);
        $auth->addChild($sectionAssetDelete, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetDelete);

        $sectionAssetOrder = $auth->createPermission('sectionAssetOrder');
        $sectionAssetOrder->description = 'Change section asset order';
        $auth->add($sectionAssetOrder);

        $auth->addChild($sectionAssetOrder, $sectionUpdate);
        $auth->addChild($sectionAssetOrder, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetOrder);
    }

    public function safeDown(): void
    {
        $auth = Yii::$app->getAuthManager();

        $this->delete($auth->itemTable, ['name' => 'categoryOrder']);
        $this->delete($auth->itemTable, ['name' => 'categoryDelete']);
        $this->delete($auth->itemTable, ['name' => 'categoryCreate']);
        $this->delete($auth->itemTable, ['name' => 'categoryUpdate']);

        $this->delete($auth->itemTable, ['name' => 'sectionAssetOrder']);
        $this->delete($auth->itemTable, ['name' => 'sectionAssetDelete']);
        $this->delete($auth->itemTable, ['name' => 'sectionAssetCreate']);
        $this->delete($auth->itemTable, ['name' => 'sectionAssetUpdate']);

        $this->delete($auth->itemTable, ['name' => 'sectionOrder']);
        $this->delete($auth->itemTable, ['name' => 'sectionDelete']);
        $this->delete($auth->itemTable, ['name' => 'sectionCreate']);
        $this->delete($auth->itemTable, ['name' => 'sectionUpdate']);

        $this->delete($auth->itemTable, ['name' => 'entryAssetOrder']);
        $this->delete($auth->itemTable, ['name' => 'entryAssetDelete']);
        $this->delete($auth->itemTable, ['name' => 'entryAssetCreate']);
        $this->delete($auth->itemTable, ['name' => 'entryAssetUpdate']);

        $this->delete($auth->itemTable, ['name' => 'entryCategoryUpdate']);

        $this->delete($auth->itemTable, ['name' => 'entryOrder']);
        $this->delete($auth->itemTable, ['name' => 'entryDelete']);
        $this->delete($auth->itemTable, ['name' => 'entryCreate']);
        $this->delete($auth->itemTable, ['name' => 'entryUpdate']);
    }
}
