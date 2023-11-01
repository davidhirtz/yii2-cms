<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers\traits;

use davidhirtz\yii2\cms\models\Category;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Trait CategoryTrait
 * @package davidhirtz\yii2\cms\modules\admin\controllers\traits
 */
trait CategoryTrait
{
    /**
     * @param int $id
     * @param string|null $permissionName
     * @return Category
     */
    protected function findCategory($id, $permissionName = null)
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