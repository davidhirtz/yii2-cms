<?php

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;

/**
 * @template-extends \davidhirtz\yii2\skeleton\models\actions\DuplicateActiveRecord<Asset>
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

    protected function beforeDuplicate(): bool
    {
        if (!parent::beforeDuplicate()) {
            return false;
        }

        $this->duplicate->populateParentRelation(!$this->parent || $this->parent->getIsNewRecord()
            ? $this->model->parent
            : $this->parent);

        $this->duplicate->shouldUpdateParentAfterInsert = $this->shouldUpdateParentAfterInsert;

        return true;
    }
}