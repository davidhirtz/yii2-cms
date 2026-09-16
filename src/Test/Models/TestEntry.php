<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Types\EntryType;

class TestEntry extends Entry
{
    public const int TYPE_PAGE = 1;
    public const int TYPE_POST = 2;

    #[\Override]
    public function getTypes(): array
    {
        return [
            EntryType::make(self::TYPE_PAGE)
                ->name('Page')
                ->hiddenFields('content'),
            EntryType::make(self::TYPE_POST)
                ->name('Post')
                ->allowAssets(false)
                ->allowSections(false),
        ];
    }
}
