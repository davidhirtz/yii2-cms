<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use Yii;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M200929203300Rbac extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $sourceLanguage = Yii::$app->sourceLanguage;

        $auth = Yii::$app->getAuthManager();
        $author = $auth->getRole(Module::AUTH_ROLE_AUTHOR);

        // Category
        $categoryUpdate = $auth->createPermission(Category::AUTH_CATEGORY_UPDATE);
        $categoryUpdate->description = Yii::t('cms', 'Update categories', [], $sourceLanguage);
        $auth->add($categoryUpdate);

        $auth->addChild($author, $categoryUpdate);

        $categoryCreate = $auth->createPermission(Category::AUTH_CATEGORY_CREATE);
        $categoryCreate->description = Yii::t('cms', 'Create new categories', [], $sourceLanguage);
        $auth->add($categoryCreate);

        $auth->addChild($categoryCreate, $categoryUpdate);
        $auth->addChild($author, $categoryCreate);

        $categoryDelete = $auth->createPermission(Category::AUTH_CATEGORY_DELETE);
        $categoryDelete->description = Yii::t('cms', 'Delete categories', [], $sourceLanguage);
        $auth->add($categoryDelete);

        $auth->addChild($categoryDelete, $categoryUpdate);
        $auth->addChild($author, $categoryDelete);

        $categoryOrder = $auth->createPermission(Category::AUTH_CATEGORY_ORDER);
        $categoryOrder->description = Yii::t('cms', 'Change category order', [], $sourceLanguage);
        $auth->add($categoryOrder);

        $auth->addChild($categoryOrder, $categoryUpdate);
        $auth->addChild($author, $categoryOrder);

        // Entry
        $entryUpdate = $auth->createPermission(Entry::AUTH_ENTRY_UPDATE);
        $entryUpdate->description = Yii::t('cms', 'Update entries', [], $sourceLanguage);
        $auth->add($entryUpdate);

        $auth->addChild($author, $entryUpdate);

        $entryCreate = $auth->createPermission(Entry::AUTH_ENTRY_CREATE);
        $entryCreate->description = Yii::t('cms', 'Create new entries', [], $sourceLanguage);
        $auth->add($entryCreate);

        $auth->addChild($entryCreate, $entryUpdate);
        $auth->addChild($author, $entryCreate);

        $entryDelete = $auth->createPermission(Entry::AUTH_ENTRY_DELETE);
        $entryDelete->description = Yii::t('cms', 'Delete entries', [], $sourceLanguage);
        $auth->add($entryDelete);

        $auth->addChild($entryDelete, $entryUpdate);
        $auth->addChild($author, $entryDelete);

        $entryOrder = $auth->createPermission(Entry::AUTH_ENTRY_ORDER);
        $entryOrder->description = Yii::t('cms', 'Change entry order', [], $sourceLanguage);
        $auth->add($entryOrder);

        $auth->addChild($entryOrder, $entryUpdate);
        $auth->addChild($author, $entryOrder);

        // EntryCategory
        $entryCategoryUpdate = $auth->createPermission(Entry::AUTH_ENTRY_CATEGORY_UPDATE);
        $entryCategoryUpdate->description = Yii::t('cms', 'Update entry categories', [], $sourceLanguage);
        $auth->add($entryCategoryUpdate);

        $auth->addChild($entryCategoryUpdate, $entryUpdate);
        $auth->addChild($author, $entryCategoryUpdate);

        // Section
        $sectionUpdate = $auth->createPermission(Section::AUTH_SECTION_UPDATE);
        $sectionUpdate->description = Yii::t('cms', 'Update sections', [], $sourceLanguage);
        $auth->add($sectionUpdate);

        $auth->addChild($sectionUpdate, $entryUpdate);
        $auth->addChild($author, $sectionUpdate);

        $sectionCreate = $auth->createPermission(Section::AUTH_SECTION_CREATE);
        $sectionCreate->description = Yii::t('cms', 'Create new sections', [], $sourceLanguage);
        $auth->add($sectionCreate);

        $auth->addChild($sectionCreate, $entryUpdate);
        $auth->addChild($sectionCreate, $sectionUpdate);
        $auth->addChild($author, $sectionCreate);

        $sectionDelete = $auth->createPermission(Section::AUTH_SECTION_DELETE);
        $sectionDelete->description = Yii::t('cms', 'Delete sections', [], $sourceLanguage);
        $auth->add($sectionDelete);

        $auth->addChild($sectionDelete, $entryUpdate);
        $auth->addChild($sectionDelete, $sectionUpdate);
        $auth->addChild($author, $sectionDelete);

        $sectionOrder = $auth->createPermission(Section::AUTH_SECTION_ORDER);
        $sectionOrder->description = Yii::t('cms', 'Change section order', [], $sourceLanguage);
        $auth->add($sectionOrder);

        $auth->addChild($sectionOrder, $entryUpdate);
        $auth->addChild($sectionOrder, $sectionUpdate);
        $auth->addChild($author, $sectionOrder);

        // EntryAsset
        $entryAssetUpdate = $auth->createPermission(Entry::AUTH_ENTRY_ASSET_UPDATE);
        $entryAssetUpdate->description = Yii::t('cms', 'Update entry assets', [], $sourceLanguage);
        $auth->add($entryAssetUpdate);

        $auth->addChild($entryAssetUpdate, $entryUpdate);
        $auth->addChild($author, $entryAssetUpdate);

        $entryAssetCreate = $auth->createPermission(Entry::AUTH_ENTRY_ASSET_CREATE);
        $entryAssetCreate->description = Yii::t('cms', 'Create new entry assets', [], $sourceLanguage);
        $auth->add($entryAssetCreate);

        $auth->addChild($entryAssetCreate, $entryUpdate);
        $auth->addChild($entryAssetCreate, $entryAssetUpdate);
        $auth->addChild($author, $entryAssetCreate);

        $entryAssetDelete = $auth->createPermission(Entry::AUTH_ENTRY_ASSET_DELETE);
        $entryAssetDelete->description = Yii::t('cms', 'Delete entry assets', [], $sourceLanguage);
        $auth->add($entryAssetDelete);

        $auth->addChild($entryAssetDelete, $entryUpdate);
        $auth->addChild($entryAssetDelete, $entryAssetUpdate);
        $auth->addChild($author, $entryAssetDelete);

        $entryAssetOrder = $auth->createPermission('entryAssetOrder');
        $entryAssetOrder->description = Yii::t('cms', 'Change entry asset order', [], $sourceLanguage);
        $auth->add($entryAssetOrder);

        $auth->addChild($entryAssetOrder, $entryUpdate);
        $auth->addChild($entryAssetOrder, $entryAssetUpdate);
        $auth->addChild($author, $entryAssetOrder);

        // SectionAsset
        $sectionAssetUpdate = $auth->createPermission(Section::AUTH_SECTION_ASSET_UPDATE);
        $sectionAssetUpdate->description = Yii::t('cms', 'Update section assets', [], $sourceLanguage);
        $auth->add($sectionAssetUpdate);

        $auth->addChild($sectionAssetUpdate, $sectionUpdate);
        $auth->addChild($author, $sectionAssetUpdate);

        $sectionAssetCreate = $auth->createPermission(Section::AUTH_SECTION_ASSET_CREATE);
        $sectionAssetCreate->description = Yii::t('cms', 'Create new section assets', [], $sourceLanguage);
        $auth->add($sectionAssetCreate);

        $auth->addChild($sectionAssetCreate, $sectionUpdate);
        $auth->addChild($sectionAssetCreate, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetCreate);

        $sectionAssetDelete = $auth->createPermission(Section::AUTH_SECTION_ASSET_DELETE);
        $sectionAssetDelete->description = Yii::t('cms', 'Delete section assets', [], $sourceLanguage);
        $auth->add($sectionAssetDelete);

        $auth->addChild($sectionAssetDelete, $sectionUpdate);
        $auth->addChild($sectionAssetDelete, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetDelete);

        $sectionAssetOrder = $auth->createPermission(Section::AUTH_SECTION_ASSET_ORDER);
        $sectionAssetOrder->description = Yii::t('cms', 'Change section asset order', [], $sourceLanguage);
        $auth->add($sectionAssetOrder);

        $auth->addChild($sectionAssetOrder, $sectionUpdate);
        $auth->addChild($sectionAssetOrder, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetOrder);
    }

    public function safeDown(): void
    {
        $auth = Yii::$app->getAuthManager();

        $this->delete($auth->itemTable, ['name' => Category::AUTH_CATEGORY_ORDER]);
        $this->delete($auth->itemTable, ['name' => Category::AUTH_CATEGORY_DELETE]);
        $this->delete($auth->itemTable, ['name' => Category::AUTH_CATEGORY_CREATE]);
        $this->delete($auth->itemTable, ['name' => Category::AUTH_CATEGORY_UPDATE]);

        $this->delete($auth->itemTable, ['name' => Section::AUTH_SECTION_ASSET_ORDER]);
        $this->delete($auth->itemTable, ['name' => Section::AUTH_SECTION_ASSET_DELETE]);
        $this->delete($auth->itemTable, ['name' => Section::AUTH_SECTION_ASSET_CREATE]);
        $this->delete($auth->itemTable, ['name' => Section::AUTH_SECTION_ASSET_UPDATE]);

        $this->delete($auth->itemTable, ['name' => Section::AUTH_SECTION_ORDER]);
        $this->delete($auth->itemTable, ['name' => Section::AUTH_SECTION_DELETE]);
        $this->delete($auth->itemTable, ['name' => Section::AUTH_SECTION_CREATE]);
        $this->delete($auth->itemTable, ['name' => Section::AUTH_SECTION_UPDATE]);

        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_ASSET_ORDER]);
        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_ASSET_DELETE]);
        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_ASSET_CREATE]);
        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_ASSET_UPDATE]);

        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_CATEGORY_UPDATE]);

        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_ORDER]);
        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_DELETE]);
        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_CREATE]);
        $this->delete($auth->itemTable, ['name' => Entry::AUTH_ENTRY_UPDATE]);
    }
}
