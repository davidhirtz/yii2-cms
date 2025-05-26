<?php

declare(strict_types=1);

use davidhirtz\yii2\cms\tests\data\models\TestSection;
use yii\db\Expression;

$now = new Expression('UTC_TIMESTAMP()');

return [
    '3-1' => [
        'id' => 1,
        'section_id' => 3,
        'entry_id' => 1,
        'position' => 2,
        'updated_at' => $now,
    ],
    '3-6' => [
        'id' => 2,
        'section_id' => 3,
        'entry_id' => 6,
        'position' => 1,
        'updated_at' => $now,
    ],
];
