<?php

use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use yii\db\Expression;

$now = new Expression('UTC_TIMESTAMP()');
$index = 0;

return [
    'page-enabled' => [
        'id' => ++$index,
        'status' => TestEntry::STATUS_ENABLED,
        'type' => TestEntry::TYPE_PAGE,
        'name' => 'Test Page – Enabled',
        'slug' => 'test-1',
        'position' => $index,
        'asset_count' => 2,
        'section_count' => 5,
        'publish_date' => $now,
        'created_at' => $now,
    ],
    'page-draft' => [
        'id' => ++$index,
        'status' => TestEntry::STATUS_DRAFT,
        'type' => TestEntry::TYPE_PAGE,
        'name' => 'Test Page – Draft',
        'slug' => 'test-2',
        'position' => $index,
        'asset_count' => 0,
        'section_count' => 1,
        'publish_date' => $now,
        'created_at' => $now,
    ],
    'page-disabled' => [
        'id' => ++$index,
        'status' => TestEntry::STATUS_DISABLED,
        'type' => TestEntry::TYPE_PAGE,
        'name' => 'Test Page – Disabled',
        'slug' => 'test-3',
        'position' => $index,
        'asset_count' => 0,
        'section_count' => 0,
        'publish_date' => $now,
        'created_at' => $now,
    ],
];
