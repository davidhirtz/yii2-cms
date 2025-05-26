<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\support\fixtures;

use davidhirtz\yii2\cms\models\Category;
use yii\test\ActiveFixture;

class CategoryFixture extends ActiveFixture
{
    public $modelClass = Category::class;
}
