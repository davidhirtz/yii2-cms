<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Trail;
use yii\db\Query;

/**
 * @extends ReorderActiveRecords<EntryCategory>
 */
class ReorderEntryCategories extends ReorderActiveRecords
{
    /**
     * @param list<int> $entryIds
     */
    public function __construct(protected Category $category, ?int $tenantId, array $entryIds)
    {
        // A category is shared by every tenant, its entries are not.
        $tenantEntryIds = (new Query())
            ->select('id')
            ->from(Entry::tableName())
            ->where(['id' => $entryIds])
            ->andFilterWhere(['tenant_id' => $tenantId]);

        /** @var EntryCategory[] $entryCategories */
        $entryCategories = $category->getEntryCategories()
            ->andWhere(['entry_id' => $tenantEntryIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $order = array_flip($entryIds);

        parent::__construct($entryCategories, $order);
    }

    #[\Override]
    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->category, Message::make('cms', 'COMMON_ENTRY_ORDER_CHANGED'));
        parent::afterReorder();
    }
}
