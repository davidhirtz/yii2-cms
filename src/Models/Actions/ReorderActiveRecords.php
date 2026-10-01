<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Actions\ReorderActiveRecords as BaseReorderActiveRecords;
use Override;

/**
 * @template TActiveRecord of ActiveRecord
 * @template-extends BaseReorderActiveRecords<TActiveRecord>
 */
class ReorderActiveRecords extends BaseReorderActiveRecords
{
    use ModuleTrait;

    #[Override]
    protected function afterCommit(): void
    {
        static::getModule()->invalidatePageCache();
        parent::afterCommit();
    }
}
