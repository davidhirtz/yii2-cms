<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Skeleton\Test\Fixtures\ActiveFixture;

class SectionFixture extends ActiveFixture
{
    public $depends = [EntryFixture::class];
    public $modelClass = TestSection::class;
}
