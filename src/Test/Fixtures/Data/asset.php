<?php

declare(strict_types=1);

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Media\Models\Asset;
use yii\db\Expression;

return [
    'entry-asset' => [
        'id' => 1,
        'status' => Asset::STATUS_ENABLED,
        'type' => Asset::TYPE_DEFAULT,
        'model_class' => Entry::class,
        'model_id' => 1,
        'file_id' => 1,
        'position' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'entry-meta-image' => [
        'id' => 2,
        'status' => Asset::STATUS_ENABLED,
        'type' => Asset::TYPE_META_IMAGE,
        'model_class' => Entry::class,
        'model_id' => 1,
        'file_id' => 2,
        'position' => 2,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'post-asset' => [
        'id' => 3,
        'status' => Asset::STATUS_ENABLED,
        'type' => Asset::TYPE_DEFAULT,
        'model_class' => Entry::class,
        'model_id' => 6,
        'file_id' => 6,
        'position' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'section-image-1' => [
        'id' => 4,
        'status' => Asset::STATUS_ENABLED,
        'type' => Asset::TYPE_DEFAULT,
        'model_class' => Section::class,
        'model_id' => 1,
        'file_id' => 3,
        'position' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'section-image-2' => [
        'id' => 5,
        'status' => Asset::STATUS_ENABLED,
        'type' => Asset::TYPE_VIEWPORT_MOBILE,
        'model_class' => Section::class,
        'model_id' => 1,
        'file_id' => 4,
        'position' => 2,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'section-image-draft' => [
        'id' => 6,
        'status' => Asset::STATUS_DRAFT,
        'type' => Asset::TYPE_DEFAULT,
        'model_class' => Section::class,
        'model_id' => 1,
        'file_id' => 5,
        'position' => 3,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'section-image-disabled' => [
        'id' => 7,
        'status' => Asset::STATUS_DISABLED,
        'type' => Asset::TYPE_VIEWPORT_MOBILE,
        'model_class' => Section::class,
        'model_id' => 1,
        'file_id' => 5,
        'position' => 4,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
];
