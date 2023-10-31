<?php

namespace davidhirtz\yii2\cms\models\base;

use yii\base\ModelEvent;

class ModelCloneEvent extends ModelEvent
{
    public ?ActiveRecord $clone = null;
}