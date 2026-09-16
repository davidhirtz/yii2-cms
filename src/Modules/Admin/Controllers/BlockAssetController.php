<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\BlockControllerTrait;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Media\Modules\Admin\Controllers\Traits\AssetControllerTrait;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
class BlockAssetController extends Controller
{
    use AssetControllerTrait;
    use BlockControllerTrait;

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
                        'roles' => [Block::AUTH_BLOCK],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?int $block = null): Response|string
    {
        return $this->renderIndex($this->findBlockWithAssets($block));
    }

    public function actionCreate(
        ?int $block = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null,
        ?int $asset = null,
    ): Response|string {
        $model = $this->findBlockWithAssets($block);

        return $this->createAsset($model, $file, $folder, $q, $asset ? $this->findBlockAsset($asset) : null);
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->updateAsset($this->findBlockAsset($id));
    }

    public function actionDelete(int $id): Response|string
    {
        return $this->deleteAsset($this->findBlockAsset($id));
    }

    public function actionStatus(int $id): Response
    {
        return $this->updateStatus($this->findBlockAsset($id));
    }

    public function actionRemove(
        ?int $block = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null,
    ): Response|string {
        return $this->removeAsset($this->findBlockWithAssets($block), $file, $folder, $q);
    }

    public function actionDeleteAll(?int $block = null): Response
    {
        return $this->deleteAssets($this->findBlockWithAssets($block));
    }

    public function actionOrder(?int $block = null): string
    {
        return $this->reorderAssets($this->findBlockWithAssets($block));
    }

    protected function findBlockWithAssets(?int $block): Block
    {
        if (!$block) {
            throw new NotFoundHttpException();
        }

        /** @var Block */
        return $this->findAssetModel($this->findBlock($block));
    }

    protected function findBlockAsset(int $id): BlockAsset
    {
        /** @var BlockAsset */
        return $this->findAsset($id, BlockAsset::class);
    }
}
