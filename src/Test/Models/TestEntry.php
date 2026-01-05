<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Models;

use Hirtz\Cms\Models\Entry;

class TestEntry extends Entry
{
    public const int TYPE_PAGE = 1;
    public const int TYPE_POST = 2;

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
