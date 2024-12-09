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
        'entry_count' => 2,
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
    'post-1' => [
        'id' => ++$index,
        'status' => TestEntry::STATUS_ENABLED,
        'parent_status' => TestEntry::STATUS_ENABLED,
        'type' => TestEntry::TYPE_POST,
        'parent_id' => 1,
        'name' => 'Test Child 1',
        'slug' => 'post-1',
        'parent_slug' => 'post-1',
        'path' => '1',
        'position' => 1,
        'asset_count' => 0,
        'section_count' => 0,
        'publish_date' => $now,
        'created_at' => $now,
    ],
    'post-2' => [
        'id' => ++$index,
        'status' => TestEntry::STATUS_ENABLED,
        'parent_status' => TestEntry::STATUS_ENABLED,
        'type' => TestEntry::TYPE_POST,
        'parent_id' => 1,
        'name' => 'Test Child 2',
        'slug' => 'post-2',
        'parent_slug' => 'post-1',
        'path' => '1',
        'position' => 2,
        'asset_count' => 0,
        'section_count' => 0,
        'publish_date' => $now,
        'created_at' => $now,
    ],
];
