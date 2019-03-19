<?php

namespace davidhirtz\yii2\cms\components;

use davidhirtz\yii2\cms\Module;
use Yii;

/**
 * Trait ModuleTrait
 * @package davidhirtz\yii2\cms\components
 */
trait ModuleTrait
{
    /**
     * @var Module
     */
    protected static $_module;

    /**
     * @return Module
     */
    public static function getModule()
    {
        if (static::$_module === null) {
            static::$_module = Yii::$app->getModule('cms');
        }

        return static::$_module;
    }
}