<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Block;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Trail;
use Override;

/**
 * @template-extends ReorderActiveRecords<Block>
 */
class ReorderBlocks extends ReorderActiveRecords
{
    /**
     * @param list<int> $blockIds
     */
    public function __construct(array $blockIds = [])
    {
        $blocks = Block::find()
            ->select(['id', 'position'])
            ->andWhere(['id' => $blockIds])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        parent::__construct($blocks, array_flip($blockIds));
    }

    #[Override]
    protected function afterReorder(): void
    {
        Trail::createOrderTrail(null, Message::make('cms', 'REORDER_BLOCKS_BLOCK_ORDER_CHANGED'));
        parent::afterReorder();
    }
}
