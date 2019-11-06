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
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $entry = new Entry;
        foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
            try {
                $this->dropIndex($attributeName, Entry::tableName());
            } catch (\Exception $ex) {
            }

            $this->createIndex($attributeName, Entry::tableName(), $attributeName, true);
        }

        $section = new Section;
        foreach ($section->getI18nAttributeNames('slug') as $attributeName) {
            try {
                $this->dropIndex($attributeName, Section::tableName());
            } catch (\Exception $ex) {
            }

            $this->createIndex($attributeName, Section::tableName(), ['entry_id', $attributeName], true);
        }

        $category = new Category;
        foreach ($category->getI18nAttributeNames('slug') as $attributeName) {
            try {
                $this->dropIndex($attributeName, Category::tableName());
            } catch (\Exception $ex) {
            }

            $this->createIndex($attributeName, Category::tableName(), ['parent_id', $attributeName], true);
        }
    }
}
