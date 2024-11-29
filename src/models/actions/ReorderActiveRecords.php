<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\actions\ReorderActiveRecords as BaseReorderActiveRecords;

/**
 * @template TActiveRecord of ActiveRecord
 * @template-extends BaseReorderActiveRecords<TActiveRecord>
 */
class ReorderActiveRecords extends BaseReorderActiveRecords
{
    use ModuleTrait;

    protected function afterReorder(): void
    {
        static::getModule()->invalidatePageCache();
        parent::afterReorder();
    }
}
