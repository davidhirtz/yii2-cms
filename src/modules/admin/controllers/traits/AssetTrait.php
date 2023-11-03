<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers\traits;

use davidhirtz\yii2\cms\models\Asset;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait AssetTrait
{
    protected function findAsset(int $id, ?string $permissionName = null): Asset
    {
        if (!$asset = Asset::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if (in_array($permissionName, ['assetDelete', 'assetUpdate'])) {
            $permissionName = ($asset->isEntryAsset() ? 'entry' : 'section') . ucfirst($permissionName);
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['asset' => $asset])) {
            throw new ForbiddenHttpException();
        }

        return $asset;
    }
}