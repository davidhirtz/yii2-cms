<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Actions\ReorderActiveRecords as BaseReorderActiveRecords;

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
