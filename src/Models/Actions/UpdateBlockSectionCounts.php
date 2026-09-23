<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Block;

/**
 * `section.block_id` has no count column on the section's side, so both the block a section moved to and the one it
 * left have to be recounted. A caller deleting or inserting sections in batch runs this once, for every block the
 * batch touched.
 */
class UpdateBlockSectionCounts
{
    /**
     * @var list<int>
     */
    protected array $blockIds;

    /**
     * @param list<int|null> $blockIds
     */
    public function __construct(array $blockIds)
    {
        $this->blockIds = array_values(array_unique(array_filter(array_map(intval(...), $blockIds))));
    }

    public function update(): void
    {
        foreach ($this->blockIds ? Block::findAll(['id' => $this->blockIds]) : [] as $block) {
            $block->updateSectionCount();
        }
    }
}
