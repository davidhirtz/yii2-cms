<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Models\Category;
use yii\test\ActiveFixture;

class CategoryFixture extends ActiveFixture
{
    public $modelClass = Category::class;
}
