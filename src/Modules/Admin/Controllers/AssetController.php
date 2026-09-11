<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Hirtz\Media\Modules\Admin\Controllers\AbstractAssetController;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The views come from the cms admin module, which is what the controller is mounted under.
 */
class AssetController extends AbstractAssetController
{
    use EntryControllerTrait;
    use SectionControllerTrait;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'update'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_UPDATE, Section::AUTH_SECTION_ASSET_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'duplicate'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_CREATE, Section::AUTH_SECTION_ASSET_CREATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_DELETE, Section::AUTH_SECTION_ASSET_DELETE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_ORDER, Section::AUTH_SECTION_ASSET_ORDER],
                    ],
                ],
            ],
        ];
    }

    #[Override]
    public function actionIndex(?int $entry = null, ?int $section = null): Response|string
    {
        return $this->renderIndex($this->findModel(
            $entry,
            $section,
            Entry::AUTH_ENTRY_ASSET_UPDATE,
            Section::AUTH_SECTION_ASSET_UPDATE,
        ));
    }

    #[Override]
    public function actionCreate(
        ?int $entry = null,
        ?int $section = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null
    ): Response|string {
        $model = $this->findModel(
            $entry,
            $section,
            Entry::AUTH_ENTRY_ASSET_CREATE,
            Section::AUTH_SECTION_ASSET_CREATE,
        );

        return $this->createAsset($model, $file, $folder, $q);
    }

    #[Override]
    public function actionUpdate(int $id): Response|string
    {
        return $this->updateAsset($this->findCmsAsset(
            $id,
            Entry::AUTH_ENTRY_ASSET_UPDATE,
            Section::AUTH_SECTION_ASSET_UPDATE,
        ));
    }

    #[Override]
    public function actionDelete(int $id): Response|string
    {
        return $this->deleteAsset($this->findCmsAsset(
            $id,
            Entry::AUTH_ENTRY_ASSET_DELETE,
            Section::AUTH_SECTION_ASSET_DELETE,
        ));
    }

    #[Override]
    public function actionDuplicate(int $id): Response|string
    {
        return $this->duplicateAsset($this->findCmsAsset(
            $id,
            Entry::AUTH_ENTRY_ASSET_CREATE,
            Section::AUTH_SECTION_ASSET_CREATE,
        ));
    }

    #[Override]
    public function actionOrder(?int $entry = null, ?int $section = null): string
    {
        return $this->reorderAssets($this->findModel(
            $entry,
            $section,
            Entry::AUTH_ENTRY_ASSET_ORDER,
            Section::AUTH_SECTION_ASSET_ORDER,
        ));
    }

    protected function findModel(
        ?int $entry,
        ?int $section,
        string $entryPermission,
        string $sectionPermission
    ): AssetModelInterface {
        if ($section) {
            return $this->findAssetModel($this->findSection($section, $sectionPermission));
        }

        if ($entry) {
            return $this->findAssetModel($this->findEntry($entry, $entryPermission));
        }

        throw new NotFoundHttpException();
    }

    protected function findCmsAsset(int $id, string $entryPermission, string $sectionPermission): Asset
    {
        $asset = $this->findAsset($id, EntryAsset::class, SectionAsset::class);
        $model = $asset->model;

        $permissionName = $asset instanceof EntryAsset ? $entryPermission : $sectionPermission;

        if (!Yii::$app->getUser()->can($permissionName, ['asset' => $asset, $model->getParamName() => $model])) {
            throw new ForbiddenHttpException();
        }

        return $asset;
    }
}
