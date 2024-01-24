<?php

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;

class ReorderCategories extends ReorderActiveRecords
{
    public function __construct(protected ?Category $parent, array $categoryIds)
    {
        parent::__construct([], array_flip($categoryIds));
    }

    protected function reorderActiveRecordsInternal(): void
    {
        Category::rebuildNestedTree($this->parent, $this->order);
    }

    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->parent, Yii::t('cms', 'Category order changed'));

        if ($this->parent) {
            $this->parent->updated_at = new DateTime();
            $this->parent->update();
        }

        parent::afterReorder();
    }
}
