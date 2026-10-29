<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Block;

/**
 * A batch deleting or inserting sections runs this once, for every block it touched.
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
