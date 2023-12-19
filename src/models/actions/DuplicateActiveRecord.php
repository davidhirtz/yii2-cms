<?php

namespace davidhirtz\yii2\cms\models\actions;


use davidhirtz\yii2\cms\models\ActiveRecord;
use davidhirtz\yii2\skeleton\models\actions\DuplicateActiveRecord as BaseDuplicateActiveRecord;
use davidhirtz\yii2\skeleton\models\interfaces\DraftStatusAttributeInterface;

/**
 * @template TActiveRecord of ActiveRecord
 * @template-extends BaseDuplicateActiveRecord<TActiveRecord>
 */
class DuplicateActiveRecord extends BaseDuplicateActiveRecord
{
    public int $defaultStatus = DraftStatusAttributeInterface::STATUS_DRAFT;

    public function __construct(protected ActiveRecord $model, array $attributes = [])
    {
        $attributes['status'] ??= $this->defaultStatus;
        parent::__construct($model, $attributes);
    }
}