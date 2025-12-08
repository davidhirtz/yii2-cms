<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support\fixtures;

use Hirtz\Cms\models\Category;
use yii\test\ActiveFixture;

class CategoryFixture extends ActiveFixture
{
    public $modelClass = Category::class;
}
