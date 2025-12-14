<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait AssetControllerTrait
{
    protected function findAsset(int $id, ?string $permissionName = null): Asset
    {
        if (!$asset = Asset::findOne($id)) {
            throw new NotFoundHttpException();
        }

        $permissionName = match ($permissionName) {
            Asset::AUTH_ASSET_DELETE => $asset->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_DELETE : Section::AUTH_SECTION_ASSET_DELETE,
            Asset::AUTH_ASSET_UPDATE => $asset->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_UPDATE : Section::AUTH_SECTION_ASSET_UPDATE,
            default => $permissionName,
        };

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['asset' => $asset])) {
            throw new ForbiddenHttpException();
        }

        return $asset;
    }
}
