<?php

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\modules\ModuleTrait;

class ReorderActiveRecords extends \davidhirtz\yii2\skeleton\models\actions\ReorderActiveRecords
{
    use ModuleTrait;

    protected function afterReorder(): void
    {
        static::getModule()->invalidatePageCache();
        parent::afterReorder();
    }
}