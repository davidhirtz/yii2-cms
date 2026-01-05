<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Test\Models\TestAsset;
use Hirtz\Media\Test\Fixtures\FileFixture;
use yii\test\ActiveFixture;

class AssetFixture extends ActiveFixture
{
    public $depends = [
        EntryFixture::class,
        FileFixture::class,
        SectionFixture::class,
    ];

    public $modelClass = TestAsset::class;
}
