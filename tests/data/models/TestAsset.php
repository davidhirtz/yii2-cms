<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\data\Models;

use Hirtz\Cms\Models\Asset;
use Hirtz\Media\Models\Traits\MetaImageTrait;

class TestAsset extends Asset
{
    use MetaImageTrait;
}
