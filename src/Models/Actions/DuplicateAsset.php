<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;

/**
 * @extends DuplicateActiveRecord<Asset>
 */
class DuplicateAsset extends DuplicateActiveRecord
{
    public function __construct(
        protected Asset $asset,
        protected Entry|Section|null $parent = null,
        protected bool $shouldUpdateParentAfterInsert = true,
        array $attributes = []
    ) {
        parent::__construct($asset, $attributes);
    }

    #[\Override]
    protected function beforeDuplicate(): bool
    {
        $this->duplicate->populateParentRelation(!$this->parent || $this->parent->getIsNewRecord()
            ? $this->model->parent
            : $this->parent);

        $this->duplicate->shouldUpdateParentAfterInsert = $this->shouldUpdateParentAfterInsert;

        return parent::beforeDuplicate();
    }
}
