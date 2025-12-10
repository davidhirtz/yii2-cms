<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support\fixtures;

use Hirtz\Cms\tests\data\Models\TestSection;
use yii\test\ActiveFixture;

class SectionFixture extends ActiveFixture
{
    public $modelClass = TestSection::class;
}
