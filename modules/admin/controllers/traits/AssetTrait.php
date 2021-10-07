<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers\traits;

use davidhirtz\yii2\cms\models\Asset;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Trait AssetTrait
 * @package davidhirtz\yii2\cms\modules\admin\controllers\traits
 */
trait AssetTrait
{
    /**
     * @param int $id
     * @param string|null $permissionName
     * @return Asset
     */
    protected function findAsset($id, $permissionName = null)
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