<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\controllers\traits;

use Hirtz\Cms\models\Entry;
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
