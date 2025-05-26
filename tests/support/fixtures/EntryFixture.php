<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\support\fixtures;

use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use yii\test\ActiveFixture;

class EntryFixture extends ActiveFixture
{
    public $modelClass = TestEntry::class;
}
