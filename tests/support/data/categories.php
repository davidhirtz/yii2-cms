<?php

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use yii\db\Expression;

$now = new Expression('UTC_TIMESTAMP()');

return [
    'root-1' => [
        'id' => 1,
        'status' => Category::STATUS_ENABLED,
        'name' => 'Root category 1',
        'slug' => 'root-1',
        'lft' => 1,
        'rgt' => 4,
        'entry_count' => 2,
        'created_at' => $now,
    ],
    'root-2' => [
        'id' => 2,
        'status' => Category::STATUS_ENABLED,
        'name' => 'Root category 2',
        'slug' => 'root-2',
        'lft' => 5,
        'rgt' => 6,
        'entry_count' => 0,
        'created_at' => $now,
    ],
    'child-1' => [
        'id' => 3,
        'status' => Category::STATUS_ENABLED,
        'name' => 'Child category 1',
        'slug' => 'child-1',
        'parent_id' => 1,
        'lft' => 2,
        'rgt' => 3,
        'entry_count' => 1,
        'created_at' => $now,
    ],
];
