<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Models;

use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Traits\MetaImageTrait;

class TestEntryAsset extends EntryAsset
{
    use MetaImageTrait;
}
