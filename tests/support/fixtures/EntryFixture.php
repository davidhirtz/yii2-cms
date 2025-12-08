<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support\fixtures;

use Hirtz\Cms\tests\data\models\TestEntry;
use yii\test\ActiveFixture;

class EntryFixture extends ActiveFixture
{
    public $modelClass = TestEntry::class;
}
