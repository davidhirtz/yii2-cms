<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Media\Modules\Admin\Controllers\Traits\AssetControllerTrait;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EntryAssetController extends Controller
{
    use AssetControllerTrait;
    use EntryControllerTrait;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'verbs' => $this->getAssetVerbs(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'update'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'duplicate'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_CREATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_DELETE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_ORDER],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?int $id = null): Response|string
    {
        return $this->renderIndex($this->findEntryWithAssets($id, Entry::AUTH_ENTRY_ASSET_UPDATE));
    }

    public function actionCreate(
        ?int $id = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null
    ): Response|string {
        $model = $this->findEntryWithAssets($id, Entry::AUTH_ENTRY_ASSET_CREATE);

        return $this->createAsset($model, $file, $folder, $q);
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->updateAsset($this->findEntryAsset($id, Entry::AUTH_ENTRY_ASSET_UPDATE));
    }

    public function actionDelete(int $id): Response|string
    {
        return $this->deleteAsset($this->findEntryAsset($id, Entry::AUTH_ENTRY_ASSET_DELETE));
    }

    public function actionDuplicate(int $id): Response|string
    {
        return $this->duplicateAsset($this->findEntryAsset($id, Entry::AUTH_ENTRY_ASSET_CREATE));
    }

    public function actionOrder(?int $id = null): string
    {
        return $this->reorderAssets($this->findEntryWithAssets($id, Entry::AUTH_ENTRY_ASSET_ORDER));
    }

    protected function findEntryWithAssets(?int $id, string $permissionName): Entry
    {
        if (!$id) {
            throw new NotFoundHttpException();
        }

        /** @var Entry */
        return $this->findAssetModel($this->findEntry($id, $permissionName));
    }

    protected function findEntryAsset(int $id, string $permissionName): EntryAsset
    {
        /** @var EntryAsset $asset */
        $asset = $this->findAsset($id, EntryAsset::class);

        if (!Yii::$app->getUser()->can($permissionName, ['asset' => $asset, 'entry' => $asset->model])) {
            throw new ForbiddenHttpException();
        }

        return $asset;
    }
}
