<?php

namespace davidhirtz\yii2\cms\models;

use yii\base\ModelEvent;

class ModelCloneEvent extends ModelEvent
{
    public ?ActiveRecord $clone = null;
}