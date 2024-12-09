<?php

use davidhirtz\yii2\cms\tests\data\models\TestSection;
use yii\db\Expression;

$now = new Expression('UTC_TIMESTAMP()');
$index = 1;

return [
    'section-headline' => [
        'id' => $index++,
        'status' => TestSection::STATUS_ENABLED,
        'type' => TestSection::TYPE_HEADLINE,
        'entry_id' => 1,
        'name' => 'Test Headline',
        'content' => '<p>Test Headline Content</p>',
        'asset_count' => 2,
        'position' => 1,
        'created_at' => $now,
    ],
    'section-column' => [
        'id' => $index++,
        'status' => TestSection::STATUS_ENABLED,
        'type' => TestSection::TYPE_COLUMN,
        'entry_id' => 1,
        'name' => 'Test Column',
        'content' => '<p>Test Column Content</p>',
        'position' => 1,
        'created_at' => $now,
    ],
    'section-draft' => [
        'id' => $index++,
        'status' => TestSection::STATUS_DRAFT,
        'type' => TestSection::TYPE_COLUMN,
        'entry_id' => 1,
        'name' => 'Test Draft Column',
        'content' => '<p>Test Draft Column Content</p>',
        'position' => 1,
        'created_at' => $now,
    ],
    'section-disabled' => [
        'id' => $index++,
        'status' => TestSection::STATUS_DISABLED,
        'type' => TestSection::TYPE_COLUMN,
        'entry_id' => 1,
        'name' => 'Test Disabled Column',
        'content' => '<p>Test Disabled Column Content</p>',
        'position' => 1,
        'created_at' => $now,
    ],
    'section-gallery' => [
        'id' => $index++,
        'status' => TestSection::STATUS_ENABLED,
        'type' => TestSection::TYPE_GALLERY,
        'entry_id' => 1,
        'name' => 'Test Gallery',
        'content' => '<p>Test Gallery Content</p>',
        'position' => 1,
        'created_at' => $now,
    ],
    'section-entry-draft' => [
        'id' => $index++,
        'status' => TestSection::STATUS_ENABLED,
        'type' => TestSection::TYPE_DEFAULT,
        'entry_id' => 2,
        'position' => 1,
        'created_at' => $now,
    ],
];
