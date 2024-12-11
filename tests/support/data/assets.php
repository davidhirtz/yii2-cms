<?php

use davidhirtz\yii2\cms\tests\data\models\TestAsset;
use yii\db\Expression;

$now = new Expression('UTC_TIMESTAMP()');

return [
    'entry-asset' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_DEFAULT,
        'entry_id' => 1,
        'file_id' => 1,
        'position' => 1,
        'created_at' => $now,
    ],
    'entry-meta-image' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_META_IMAGE,
        'entry_id' => 1,
        'file_id' => 2,
        'position' => 2,
        'created_at' => $now,
    ],
    'section-image-1' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_DEFAULT,
        'entry_id' => 1,
        'section_id' => 1,
        'file_id' => 3,
        'position' => 1,
        'created_at' => $now,
    ],
    'section-image-2' => [
        'status' => TestAsset::STATUS_ENABLED,
        'type' => TestAsset::TYPE_VIEWPORT_MOBILE,
        'entry_id' => 1,
        'section_id' => 1,
        'file_id' => 4,
        'position' => 2,
        'created_at' => $now,
    ],
    'section-image-draft' => [
        'status' => TestAsset::STATUS_DRAFT,
        'type' => TestAsset::TYPE_DEFAULT,
        'entry_id' => 1,
        'section_id' => 1,
        'file_id' => 5,
        'position' => 3,
        'created_at' => $now,
    ],
    'section-image-disabled' => [
        'status' => TestAsset::STATUS_DISABLED,
        'type' => TestAsset::TYPE_VIEWPORT_MOBILE,
        'entry_id' => 1,
        'section_id' => 1,
        'file_id' => 5,
        'position' => 4,
        'created_at' => $now,
    ],
];