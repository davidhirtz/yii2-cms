<?php

namespace davidhirtz\yii2\cms\migrations;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\MigrationTrait;
use yii\db\Migration;

/**
 * Class M191106150324Section
 */
class M191106150324Slugs extends Migration
{
    use ModuleTrait, MigrationTrait;

    /**
     * @inheritDoc
     */
    public function safeUp()
    {
        $category = Category::instance();
        foreach ($category->getI18nAttributeNames('slug') as $attributeName) {
            try {
                $this->dropIndex($attributeName, Category::tableName());
            } catch (\Exception $ex) {
            }

            $this->createIndex($attributeName, Category::tableName(), $category->slugTargetAttribute ? array_merge($category->slugTargetAttribute, [$attributeName]) : $attributeName, true);
        }

        $entry = Entry::instance();
        foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
            try {
                $this->dropIndex($attributeName, Entry::tableName());
            } catch (\Exception $ex) {
            }

            $this->createIndex($attributeName, Entry::tableName(), $entry->slugTargetAttribute ? array_merge($entry->slugTargetAttribute, [$attributeName]) : $attributeName, true);
        }

        $section = Section::instance();
        foreach ($section->getI18nAttributeNames('slug') as $attributeName) {
            try {
                $this->dropIndex($attributeName, Section::tableName());
            } catch (\Exception $ex) {
            }

            $this->createIndex($attributeName, Section::tableName(), $section->slugTargetAttribute ? array_merge($section->slugTargetAttribute, [$attributeName]) : $attributeName, true);
        }
    }
}
