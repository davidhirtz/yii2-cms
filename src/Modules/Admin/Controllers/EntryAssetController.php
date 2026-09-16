<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Media\Modules\Admin\Controllers\Traits\AssetControllerTrait;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
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
                        'actions' => [
                            'create',
                            'delete',
                            'delete-all',
                            'index',
                            'order',
                            'remove',
                            'status',
                            'update',
                        ],
                        'roles' => [Entry::AUTH_ENTRY],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?int $entry = null): Response|string
    {
        return $this->renderIndex($this->findEntryWithAssets($entry));
    }

    public function actionCreate(
        ?int $entry = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null,
        ?int $asset = null,
    ): Response|string {
        $model = $this->findEntryWithAssets($entry);

        return $this->createAsset($model, $file, $folder, $q, $asset ? $this->findEntryAsset($asset) : null);
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->updateAsset($this->findEntryAsset($id));
    }

    public function actionDelete(int $id): Response|string
    {
        return $this->deleteAsset($this->findEntryAsset($id));
    }

    public function actionStatus(int $id): Response
    {
        return $this->updateStatus($this->findEntryAsset($id));
    }

    public function actionRemove(
        ?int $entry = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null,
    ): Response|string {
        return $this->removeAsset($this->findEntryWithAssets($entry), $file, $folder, $q);
    }

    public function actionDeleteAll(?int $entry = null): Response
    {
        return $this->deleteAssets($this->findEntryWithAssets($entry));
    }

    public function actionOrder(?int $entry = null): string
    {
        return $this->reorderAssets($this->findEntryWithAssets($entry));
    }

    protected function findEntryWithAssets(?int $entry): Entry
    {
        if (!$entry) {
            throw new NotFoundHttpException();
        }

        /** @var Entry */
        return $this->findAssetModel($this->findEntry($entry));
    }

    protected function findEntryAsset(int $id): EntryAsset
    {
        /** @var EntryAsset */
        return $this->findAsset($id, EntryAsset::class);
    }
}
