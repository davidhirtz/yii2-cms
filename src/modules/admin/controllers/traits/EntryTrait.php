<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers\traits;

use davidhirtz\yii2\cms\models\Entry;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait EntryTrait
{
    protected function findEntry(int $id, ?string $permissionName = null): Entry
    {
        if (!$entry = Entry::findOne($id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['entry' => $entry])) {
            throw new ForbiddenHttpException();
        }

        return $entry;
    }
}
