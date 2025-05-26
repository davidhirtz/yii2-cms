<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\data\models;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\models\traits\MetaImageTrait;

class TestAsset extends Asset
{
    use MetaImageTrait;
}
