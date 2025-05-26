<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\data\models;

use davidhirtz\yii2\cms\models\Entry;

class TestEntry extends Entry
{
    public const TYPE_PAGE = 1;
    public const TYPE_POST = 2;

    public static function getTypes(): array
    {
        return [
            self::TYPE_PAGE => [
                'name' => 'Page',
                'hiddenFields' => ['content'],
            ],
            self::TYPE_POST => [
                'name' => 'Post',
                'hiddenFields' => ['#assets'],
            ],
        ];
    }
}
