<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Category;
use yii\web\NotFoundHttpException;

trait CategoryControllerTrait
{
    protected function findCategory(int $id): Category
    {
        if (!$category = Category::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        return $category;
    }
}
