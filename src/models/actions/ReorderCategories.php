<?php

declare(strict_types=1);

namespace Hirtz\Cms\models\actions;

use Hirtz\Cms\models\Category;
use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\models\Trail;
use Yii;

class ReorderCategories extends ReorderActiveRecords
{
    public function __construct(protected ?Category $parent, array $categoryIds)
    {
        parent::__construct([], array_flip($categoryIds));
    }

    #[\Override]
    protected function reorderActiveRecordsInternal(): void
    {
        Category::rebuildNestedTree($this->parent, $this->order);
    }

    #[\Override]
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
