<?php

namespace davidhirtz\yii2\cms\modules;

use davidhirtz\yii2\cms\Module;
use Yii;

trait ModuleTrait
{
    protected static ?Module $_module = null;

    public static function getModule(): Module
    {
        static::$_module ??= Yii::$app->getModule('cms');
        return static::$_module;
    }
}