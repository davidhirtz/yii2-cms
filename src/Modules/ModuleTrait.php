<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules;

use Hirtz\Cms\Module;
use Yii;

trait ModuleTrait
{
    public static function getModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('cms');
        return $module;
    }
}
