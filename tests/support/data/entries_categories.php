<?php

declare(strict_types=1);

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use yii\db\Expression;

$now = new Expression('UTC_TIMESTAMP()');

return [
    '1-1' => [
        'entry_id' => 1,
        'category_id' => 1,
        'position' => 1,
        'updated_at' => $now,
    ],
    '1-3' => [
        'entry_id' => 1,
        'category_id' => 3,
        'position' => 1,
        'updated_at' => $now,
    ],
    '2-1' => [
        'entry_id' => 2,
        'category_id' => 1,
        'position' => 2,
        'updated_at' => $now,
    ],
    '3-2' => [
        'entry_id' => 3,
        'category_id' => 2,
        'position' => 1,
        'updated_at' => $now,
    ],
];
