<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
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

    public function actionIndex(?int $section = null): Response|string
    {
        return $this->renderIndex($this->findSectionWithAssets($section));
    }

    public function actionCreate(
        ?int $section = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null,
        ?int $asset = null,
    ): Response|string {
        $model = $this->findSectionWithAssets($section);

        return $this->createAsset($model, $file, $folder, $q, $asset ? $this->findSectionAsset($asset) : null);
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->updateAsset($this->findSectionAsset($id));
    }

    public function actionDelete(int $id): Response|string
    {
        return $this->deleteAsset($this->findSectionAsset($id));
    }

    public function actionStatus(int $id): Response
    {
        return $this->updateStatus($this->findSectionAsset($id));
    }

    public function actionRemove(
        ?int $section = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null,
    ): Response|string {
        return $this->removeAsset($this->findSectionWithAssets($section), $file, $folder, $q);
    }

    public function actionDeleteAll(?int $section = null): Response
    {
        return $this->deleteAssets($this->findSectionWithAssets($section));
    }

    public function actionOrder(?int $section = null): string
    {
        return $this->reorderAssets($this->findSectionWithAssets($section));
    }

    protected function findSectionWithAssets(?int $section): Section
    {
        if (!$section) {
            throw new NotFoundHttpException();
        }

        /** @var Section */
        return $this->findAssetModel($this->findSection($section));
    }

    protected function findSectionAsset(int $id): SectionAsset
    {
        /** @var SectionAsset */
        return $this->findAsset($id, SectionAsset::class);
    }
}
