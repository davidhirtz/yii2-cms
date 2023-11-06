<?php

namespace davidhirtz\yii2\cms\models\actions;


use davidhirtz\yii2\skeleton\db\ActiveRecord;

class DuplicateActiveRecord extends \davidhirtz\yii2\skeleton\models\actions\DuplicateActiveRecord
{
    public int $defaultStatus = ActiveRecord::STATUS_DRAFT;

    public function __construct(protected ActiveRecord $model, array $attributes = [])
    {
        $attributes['status'] ??= $this->defaultStatus;
        parent::__construct($model, $attributes);
    }
}