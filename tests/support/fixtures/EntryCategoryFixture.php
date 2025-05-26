<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\support\fixtures;

use davidhirtz\yii2\cms\models\EntryCategory;
use yii\test\ActiveFixture;

class EntryCategoryFixture extends ActiveFixture
{
    public $modelClass = EntryCategory::class;
}
