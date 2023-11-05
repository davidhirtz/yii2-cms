<?php

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\modules\ModuleTrait;

class ReorderActiveRecordsAction extends \davidhirtz\yii2\skeleton\db\actions\ReorderActiveRecordsAction
{
    use ModuleTrait;

    protected function afterReorder(): void
    {
        static::getModule()->invalidatePageCache();
        parent::afterReorder();
    }
}