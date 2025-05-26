<?php

declare(strict_types=1);

use davidhirtz\yii2\cms\tests\data\models\TestSection;
use yii\db\Expression;

$now = new Expression('UTC_TIMESTAMP()');

return [
    'section-headline' => [
        'id' => 1,
        'status' => TestSection::STATUS_ENABLED,
        'type' => TestSection::TYPE_HEADLINE,
        'entry_id' => 1,
        'name' => 'Test Headline',
        'content' => '<p>Test Headline Content</p>',
        'asset_count' => 4,
        'position' => 1,
        'created_at' => $now,
    ],
    'section-column' => [
        'id' => 2,
        'status' => TestSection::STATUS_ENABLED,
        'type' => TestSection::TYPE_TEXT_COLUMN,
        'entry_id' => 1,
        'name' => 'Test Column',
        'content' => '<p>Test Column Content</p>',
        'position' => 2,
        'created_at' => $now,
    ],
    'section-blog-draft' => [
        'id' => 3,
        'status' => TestSection::STATUS_DRAFT,
        'type' => TestSection::TYPE_BLOG,
        'entry_id' => 1,
        'name' => 'Draft Blog',
        'position' => 3,
        'entry_count' => 2,
        'created_at' => $now,
    ],
    'section-disabled' => [
        'id' => 4,
        'status' => TestSection::STATUS_DISABLED,
        'type' => TestSection::TYPE_TEXT_COLUMN,
        'entry_id' => 1,
        'name' => 'Test Disabled Column',
        'content' => '<p>Test Disabled Column Content</p>',
        'position' => 4,
        'created_at' => $now,
    ],
    'section-gallery' => [
        'id' => 5,
        'status' => TestSection::STATUS_ENABLED,
        'type' => TestSection::TYPE_GALLERY,
        'entry_id' => 1,
        'name' => 'Test Gallery',
        'content' => '<p>Test Gallery Content</p>',
        'position' => 5,
        'created_at' => $now,
    ],
    'section-entry-draft' => [
        'id' => 6,
        'status' => TestSection::STATUS_ENABLED,
        'type' => TestSection::TYPE_DEFAULT,
        'entry_id' => 2,
        'position' => 1,
        'created_at' => $now,
    ],
];
