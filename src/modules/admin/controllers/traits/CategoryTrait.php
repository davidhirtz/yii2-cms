<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\controllers\traits;

use Hirtz\Cms\models\Category;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait CategoryTrait
{
    protected function findCategory(int $id, ?string $permissionName = null): Category
    {
        if (!$category = Category::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['category' => $category])) {
            throw new ForbiddenHttpException();
        }

        return $category;
    }
}
