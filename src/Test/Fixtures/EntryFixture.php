<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Skeleton\Test\Fixtures\ActiveFixture;

class EntryFixture extends ActiveFixture
{
    public $modelClass = TestEntry::class;
}
