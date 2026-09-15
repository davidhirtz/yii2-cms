<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Skeleton\Test\Fixtures\ActiveFixture;
use yii\test\Fixture;

class SectionFixture extends ActiveFixture
{
    /**
     * @var list<class-string<Fixture>>
     */
    public $depends = [EntryFixture::class];
    public $modelClass = TestSection::class;
}
