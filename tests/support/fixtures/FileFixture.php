<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support\fixtures;

use Hirtz\Media\Models\File;
use yii\test\ActiveFixture;

class FileFixture extends ActiveFixture
{
    public $modelClass = File::class;
}
