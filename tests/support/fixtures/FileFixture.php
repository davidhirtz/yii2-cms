<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\support\fixtures;

use davidhirtz\yii2\media\models\File;
use yii\test\ActiveFixture;

class FileFixture extends ActiveFixture
{
    public $modelClass = File::class;
}
