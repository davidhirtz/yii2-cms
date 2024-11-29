<?php

use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use yii\db\Expression;

$now = new Expression('UTC_TIMESTAMP()');
$index = 0;

return [
    'page-1' => [
        'id' => ++$index,
        'status' => TestEntry::STATUS_ENABLED,
        'type' => TestEntry::TYPE_PAGE,
        'name' => 'Test Page 1',
        'slug' => 'test-page-1',
        'position' => $index,
        'asset_count' => 2,
        'section_count' => 5,
        'publish_date' => $now,
        'created_at' => $now,
    ],
    'page-2' => [
        'id' => 2,
        'status' => TestEntry::STATUS_ENABLED,
        'type' => TestEntry::TYPE_PAGE,
        'name' => 'Test Page 2',
        'slug' => 'test-page-2',
        'position' => $index,
        'asset_count' => 0,
        'section_count' => 0,
        'publish_date' => $now,
        'created_at' => $now,
    ],
];
