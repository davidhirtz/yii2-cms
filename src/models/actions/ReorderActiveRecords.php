<?php

declare(strict_types=1);

namespace Hirtz\Cms\models\actions;

use Hirtz\Cms\modules\ModuleTrait;
use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\models\actions\ReorderActiveRecords as BaseReorderActiveRecords;

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
