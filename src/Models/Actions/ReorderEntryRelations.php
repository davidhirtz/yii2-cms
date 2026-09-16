<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Trail;
use Override;

/**
 * @extends ReorderActiveRecords<EntryRelation>
 */
class ReorderEntryRelations extends ReorderActiveRecords
{
    /**
     * @param list<int> $entryRelationIds
     */
    public function __construct(protected EntryRelationModelInterface $model, array $entryRelationIds)
    {
        $entryRelations = $model->getEntryRelations()
            ->andWhere(['id' => $entryRelationIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $order = array_flip($entryRelationIds);

        parent::__construct($entryRelations, $order);
    }

    #[Override]
    protected function afterReorder(): void
    {
        Trail::createOrderTrail($this->model, Message::make('cms', 'REORDER_ENTRY_RELATIONS_LINKED'));
        parent::afterReorder();
    }
}
