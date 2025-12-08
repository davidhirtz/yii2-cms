<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Actions\DuplicateActiveRecord as BaseDuplicateActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\DraftStatusAttributeInterface;

/**
 * @template T of ActiveRecord
 * @template-extends BaseDuplicateActiveRecord<T>
 *
 * @property T $model
 * @property T $duplicate
 */
abstract class DuplicateActiveRecord extends BaseDuplicateActiveRecord
{
    public int $defaultStatus = DraftStatusAttributeInterface::STATUS_DRAFT;

    public function __construct(protected ActiveRecord $model, array $attributes = [])
    {
        $attributes['status'] ??= $this->defaultStatus;
        parent::__construct($model, $attributes);
    }
}
