<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Media\Modules\Admin\Controllers\Traits\AssetControllerTrait;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SectionAssetController extends Controller
{
    use AssetControllerTrait;
    use SectionControllerTrait;

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
                        'roles' => [Section::AUTH_SECTION_ASSET_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'duplicate'],
                        'roles' => [Section::AUTH_SECTION_ASSET_CREATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [Section::AUTH_SECTION_ASSET_DELETE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => [Section::AUTH_SECTION_ASSET_ORDER],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?int $id = null): Response|string
    {
        return $this->renderIndex($this->findSectionWithAssets($id, Section::AUTH_SECTION_ASSET_UPDATE));
    }

    public function actionCreate(
        ?int $id = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null
    ): Response|string {
        $model = $this->findSectionWithAssets($id, Section::AUTH_SECTION_ASSET_CREATE);

        return $this->createAsset($model, $file, $folder, $q);
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->updateAsset($this->findSectionAsset($id, Section::AUTH_SECTION_ASSET_UPDATE));
    }

    public function actionDelete(int $id): Response|string
    {
        return $this->deleteAsset($this->findSectionAsset($id, Section::AUTH_SECTION_ASSET_DELETE));
    }

    public function actionDuplicate(int $id): Response|string
    {
        return $this->duplicateAsset($this->findSectionAsset($id, Section::AUTH_SECTION_ASSET_CREATE));
    }

    public function actionOrder(?int $id = null): string
    {
        return $this->reorderAssets($this->findSectionWithAssets($id, Section::AUTH_SECTION_ASSET_ORDER));
    }

    protected function findSectionWithAssets(?int $id, string $permissionName): Section
    {
        if (!$id) {
            throw new NotFoundHttpException();
        }

        /** @var Section */
        return $this->findAssetModel($this->findSection($id, $permissionName));
    }

    protected function findSectionAsset(int $id, string $permissionName): SectionAsset
    {
        /** @var SectionAsset $asset */
        $asset = $this->findAsset($id, SectionAsset::class);

        if (!Yii::$app->getUser()->can($permissionName, ['asset' => $asset, 'section' => $asset->model])) {
            throw new ForbiddenHttpException();
        }

        return $asset;
    }
}
