<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\skeleton\db\MigrationTrait;
use Yii;
use yii\db\Migration;

/**
* @noinspection PhpUnused
*/
class M200929203300Rbac extends Migration
{
    use MigrationTrait;

    /**
     * @inheritDoc
     */
    public function safeUp()
    {
        $sourceLanguage = Yii::$app->sourceLanguage;

        $auth = Yii::$app->getAuthManager();
        $author = $auth->getRole(Module::AUTH_ROLE_AUTHOR);

        // Category.
        $categoryUpdate = $auth->createPermission('categoryUpdate');
        $categoryUpdate->description = Yii::t('cms', 'Update categories', [], $sourceLanguage);
        $auth->add($categoryUpdate);

        $auth->addChild($author, $categoryUpdate);

        $categoryCreate = $auth->createPermission('categoryCreate');
        $categoryCreate->description = Yii::t('cms', 'Create new categories', [], $sourceLanguage);
        $auth->add($categoryCreate);

        $auth->addChild($categoryCreate, $categoryUpdate);
        $auth->addChild($author, $categoryCreate);

        $categoryDelete = $auth->createPermission('categoryDelete');
        $categoryDelete->description = Yii::t('cms', 'Delete categories', [], $sourceLanguage);
        $auth->add($categoryDelete);

        $auth->addChild($categoryDelete, $categoryUpdate);
        $auth->addChild($author, $categoryDelete);

        $categoryOrder = $auth->createPermission('categoryOrder');
        $categoryOrder->description = Yii::t('cms', 'Change category order', [], $sourceLanguage);
        $auth->add($categoryOrder);

        $auth->addChild($categoryOrder, $categoryUpdate);
        $auth->addChild($author, $categoryOrder);

        // Entry.
        $entryUpdate = $auth->createPermission('entryUpdate');
        $entryUpdate->description = Yii::t('cms', 'Update entries', [], $sourceLanguage);
        $auth->add($entryUpdate);

        $auth->addChild($author, $entryUpdate);

        $entryCreate = $auth->createPermission('entryCreate');
        $entryCreate->description = Yii::t('cms', 'Create new entries', [], $sourceLanguage);
        $auth->add($entryCreate);

        $auth->addChild($entryCreate, $entryUpdate);
        $auth->addChild($author, $entryCreate);

        $entryDelete = $auth->createPermission('entryDelete');
        $entryDelete->description = Yii::t('cms', 'Delete entries', [], $sourceLanguage);
        $auth->add($entryDelete);

        $auth->addChild($entryDelete, $entryUpdate);
        $auth->addChild($author, $entryDelete);

        $entryOrder = $auth->createPermission('entryOrder');
        $entryOrder->description = Yii::t('cms', 'Change entry order', [], $sourceLanguage);
        $auth->add($entryOrder);

        $auth->addChild($entryOrder, $entryUpdate);
        $auth->addChild($author, $entryOrder);

        // EntryCategory.
        $entryCategoryUpdate = $auth->createPermission('entryCategoryUpdate');
        $entryCategoryUpdate->description = Yii::t('cms', 'Update entry categories', [], $sourceLanguage);
        $auth->add($entryCategoryUpdate);

        $auth->addChild($entryCategoryUpdate, $entryUpdate);
        $auth->addChild($author, $entryCategoryUpdate);

        // Section.
        $sectionUpdate = $auth->createPermission('sectionUpdate');
        $sectionUpdate->description = Yii::t('cms', 'Update sections', [], $sourceLanguage);
        $auth->add($sectionUpdate);

        $auth->addChild($sectionUpdate, $entryUpdate);
        $auth->addChild($author, $sectionUpdate);

        $sectionCreate = $auth->createPermission('sectionCreate');
        $sectionCreate->description = Yii::t('cms', 'Create new sections', [], $sourceLanguage);
        $auth->add($sectionCreate);

        $auth->addChild($sectionCreate, $entryUpdate);
        $auth->addChild($sectionCreate, $sectionUpdate);
        $auth->addChild($author, $sectionCreate);

        $sectionDelete = $auth->createPermission('sectionDelete');
        $sectionDelete->description = Yii::t('cms', 'Delete sections', [], $sourceLanguage);
        $auth->add($sectionDelete);

        $auth->addChild($sectionDelete, $entryUpdate);
        $auth->addChild($sectionDelete, $sectionUpdate);
        $auth->addChild($author, $sectionDelete);

        $sectionOrder = $auth->createPermission('sectionOrder');
        $sectionOrder->description = Yii::t('cms', 'Change section order', [], $sourceLanguage);
        $auth->add($sectionOrder);

        $auth->addChild($sectionOrder, $entryUpdate);
        $auth->addChild($sectionOrder, $sectionUpdate);
        $auth->addChild($author, $sectionOrder);

        // EntryAsset.
        $entryAssetUpdate = $auth->createPermission('entryAssetUpdate');
        $entryAssetUpdate->description = Yii::t('cms', 'Update entry assets', [], $sourceLanguage);
        $auth->add($entryAssetUpdate);

        $auth->addChild($entryAssetUpdate, $entryUpdate);
        $auth->addChild($author, $entryAssetUpdate);

        $entryAssetCreate = $auth->createPermission('entryAssetCreate');
        $entryAssetCreate->description = Yii::t('cms', 'Create new entry assets', [], $sourceLanguage);
        $auth->add($entryAssetCreate);

        $auth->addChild($entryAssetCreate, $entryUpdate);
        $auth->addChild($entryAssetCreate, $entryAssetUpdate);
        $auth->addChild($author, $entryAssetCreate);

        $entryAssetDelete = $auth->createPermission('entryAssetDelete');
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

        // SectionAsset.
        $sectionAssetUpdate = $auth->createPermission('sectionAssetUpdate');
        $sectionAssetUpdate->description = Yii::t('cms', 'Update section assets', [], $sourceLanguage);
        $auth->add($sectionAssetUpdate);

        $auth->addChild($sectionAssetUpdate, $sectionUpdate);
        $auth->addChild($author, $sectionAssetUpdate);

        $sectionAssetCreate = $auth->createPermission('sectionAssetCreate');
        $sectionAssetCreate->description = Yii::t('cms', 'Create new section assets', [], $sourceLanguage);
        $auth->add($sectionAssetCreate);

        $auth->addChild($sectionAssetCreate, $sectionUpdate);
        $auth->addChild($sectionAssetCreate, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetCreate);

        $sectionAssetDelete = $auth->createPermission('sectionAssetDelete');
        $sectionAssetDelete->description = Yii::t('cms', 'Delete section assets', [], $sourceLanguage);
        $auth->add($sectionAssetDelete);

        $auth->addChild($sectionAssetDelete, $sectionUpdate);
        $auth->addChild($sectionAssetDelete, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetDelete);

        $sectionAssetOrder = $auth->createPermission('sectionAssetOrder');
        $sectionAssetOrder->description = Yii::t('cms', 'Change section asset order', [], $sourceLanguage);
        $auth->add($sectionAssetOrder);

        $auth->addChild($sectionAssetOrder, $sectionUpdate);
        $auth->addChild($sectionAssetOrder, $sectionAssetUpdate);
        $auth->addChild($author, $sectionAssetOrder);
    }

    /**
     * @inheritDoc
     */
    public function safeDown()
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