<?php

namespace davidhirtz\yii2\cms\models\base;

use yii\base\ModelEvent;

/**
 * Class ModelCloneEvent
 * @package davidhirtz\yii2\cms\models\base
 */
class ModelCloneEvent extends ModelEvent
{
    /** @var ActiveRecord */
    public $clone;
}