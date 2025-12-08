<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\data\models;

use Hirtz\Cms\Models\Entry;

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
