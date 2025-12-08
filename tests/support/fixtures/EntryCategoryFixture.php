<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support\fixtures;

use Hirtz\Cms\models\EntryCategory;
use yii\test\ActiveFixture;

class EntryCategoryFixture extends ActiveFixture
{
    public $modelClass = EntryCategory::class;
}
