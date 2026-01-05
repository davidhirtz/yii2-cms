<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Test\Models\TestEntry;
use yii\test\ActiveFixture;

class EntryFixture extends ActiveFixture
{
    public $modelClass = TestEntry::class;
}
