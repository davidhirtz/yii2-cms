<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;

/**
 * @extends ReorderActiveRecords<EntryCategory>
 */
class ReorderEntryCategories extends ReorderActiveRecords
{
    public function __construct(protected Category $category, array $entryIds)
    {
        /** @var EntryCategory[] $entryCategories */
        $entryCategories = $category->getEntryCategories()
            ->andWhere(['entry_id' => $entryIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $order = array_flip($entryIds);

        parent::__construct($entryCategories, $order);
    }

    #[\Override]
    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->category, Yii::t('cms', 'Entry order changed'));
        parent::afterReorder();
    }
}
