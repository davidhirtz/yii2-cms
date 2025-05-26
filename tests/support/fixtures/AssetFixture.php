<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\support\fixtures;

use davidhirtz\yii2\cms\tests\data\models\TestAsset;
use yii\test\ActiveFixture;

class AssetFixture extends ActiveFixture
{
    public $modelClass = TestAsset::class;
}
