<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\support\fixtures;

use davidhirtz\yii2\cms\tests\data\models\TestSection;
use yii\test\ActiveFixture;

class SectionFixture extends ActiveFixture
{
    public $modelClass = TestSection::class;
}
