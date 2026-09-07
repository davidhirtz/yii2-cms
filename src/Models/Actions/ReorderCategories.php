<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Category;
use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Models\Trail;
use Yii;

class ReorderCategories extends ReorderActiveRecords
{
    public function __construct(protected ?Category $parent, array $categoryIds)
    {
        parent::__construct([], array_flip($categoryIds));
    }

    #[\Override]
    protected function reorderActiveRecordsInternal(): int
    {
        return Category::rebuildNestedTree($this->parent, $this->order);
    }

    #[\Override]
    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->parent, Lang::t('cms', 'REORDER_CATEGORIES_CATEGORY_ORDER_CHANGED'));

        if ($this->parent) {
            $this->parent->updated_at = new DateTime();
            $this->parent->update();
        }

        parent::afterReorder();
    }
}
