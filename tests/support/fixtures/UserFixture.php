<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support\fixtures;

use Hirtz\Skeleton\models\User;
use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = User::class;
}
