<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Test\Models\TestSection;
use yii\test\ActiveFixture;

class SectionFixture extends ActiveFixture
{
    public $depends = [EntryFixture::class];
    public $modelClass = TestSection::class;
}
