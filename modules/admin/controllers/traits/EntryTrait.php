<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers\traits;

use davidhirtz\yii2\cms\models\Entry;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Trait EntryTrait
 * @package davidhirtz\yii2\cms\modules\admin\controllers\traits
 */
trait EntryTrait
{
    /**
     * @param int $id
     * @param string|null $permissionName
     * @return Entry
     */
    protected function findEntry($id, $permissionName = null)
    {
        if (!$entry = Entry::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['entry' => $entry])) {
            throw new ForbiddenHttpException();
        }

        return $entry;
    }
}