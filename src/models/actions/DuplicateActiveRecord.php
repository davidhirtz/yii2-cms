<?php

declare(strict_types=1);

namespace Hirtz\Cms\models\actions;

use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\models\actions\DuplicateActiveRecord as BaseDuplicateActiveRecord;
use Hirtz\Skeleton\models\interfaces\DraftStatusAttributeInterface;

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
