<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Models\Category;
use Hirtz\Skeleton\Test\Fixtures\ActiveFixture;

class CategoryFixture extends ActiveFixture
{
    public $modelClass = Category::class;
}
