<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\data\models;

use Hirtz\Cms\Models\Asset;
use Hirtz\Media\models\traits\MetaImageTrait;

class TestAsset extends Asset
{
    use MetaImageTrait;
}
