<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Category;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait CategoryControllerTrait
{
    protected function findCategory(int $id, ?string $permissionName = null): Category
    {
        if (!$category = Category::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !$this->webuser->can($permissionName, ['category' => $category])) {
            throw new ForbiddenHttpException();
        }

        return $category;
    }
}
