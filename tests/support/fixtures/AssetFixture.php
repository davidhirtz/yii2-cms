<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support\fixtures;

use Hirtz\Cms\tests\data\Models\TestAsset;
use yii\test\ActiveFixture;

class AssetFixture extends ActiveFixture
{
    public $modelClass = TestAsset::class;
}
