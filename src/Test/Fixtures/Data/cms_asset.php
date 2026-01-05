<?php

declare(strict_types=1);

use Hirtz\Cms\Test\Models\TestAsset;
use yii\db\Expression;

return [
    'entry-asset' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_DEFAULT,
        'entry_id' => 1,
        'file_id' => 1,
        'position' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'entry-meta-image' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_META_IMAGE,
        'entry_id' => 1,
        'file_id' => 2,
        'position' => 2,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'post-asset' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_DEFAULT,
        'entry_id' => 6,
        'file_id' => 6,
        'position' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'section-image-1' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_DEFAULT,
        'entry_id' => 1,
        'section_id' => 1,
        'file_id' => 3,
        'position' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'section-image-2' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_VIEWPORT_MOBILE,
        'entry_id' => 1,
        'section_id' => 1,
        'file_id' => 4,
        'position' => 2,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'section-image-draft' => [
        'status' => TestAsset::STATUS_DRAFT,
        'type' => TestAsset::TYPE_DEFAULT,
        'entry_id' => 1,
        'section_id' => 1,
        'file_id' => 5,
        'position' => 3,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'section-image-disabled' => [
        'status' => TestAsset::STATUS_DISABLED,
        'type' => TestAsset::TYPE_VIEWPORT_MOBILE,
        'entry_id' => 1,
        'section_id' => 1,
        'file_id' => 5,
        'position' => 4,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
];
