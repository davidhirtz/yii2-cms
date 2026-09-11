<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Actions\DuplicateAsset;
use Hirtz\Cms\Models\Actions\ReorderAssets;
use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\AssetControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Media\Models\Folder;
use Hirtz\Media\Modules\Admin\Controllers\Traits\FileControllerTrait;
use Hirtz\Media\Modules\Admin\Data\FileActiveDataProvider;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Flashes;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AssetController extends AbstractController
{
    use AssetControllerTrait;
    use EntryControllerTrait;
    use SectionControllerTrait;
    use FileControllerTrait;

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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'duplicate' => ['post'],
                    'order' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(?int $entry = null, ?int $section = null): Response|string
    {
        $parent = $this->findAssetParent(
            $entry,
            $section,
            Entry::AUTH_ENTRY_ASSET_UPDATE,
            Section::AUTH_SECTION_ASSET_UPDATE,
        );

        $provider = Yii::$container->get(AssetArrayDataProvider::class, config: [
            'parent' => $parent,
        ]);

        return $this->render('index', [
            'parent' => $parent,
            'provider' => $provider,
        ]);
    }

    public function actionCreate(
        ?int $entry = null,
        ?int $section = null,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null
    ): Response|string {
        $parent = $this->findAssetParent(
            $entry,
            $section,
            Entry::AUTH_ENTRY_ASSET_CREATE,
            Section::AUTH_SECTION_ASSET_CREATE,
        );

        if ($this->request->getIsPost()) {
            if ($file) {
                $file = $this->findFile($file);
            }

            $file ??= $this->insertFileFromRequest($folder);

            if ($this->request->preferNoContent()) {
                $this->response->setStatusCode(204);
            }

            if (!$this->response->getIsOk() || $file->hasErrors()) {
                return $this->response;
            }

            $asset = Asset::create();
            $asset->loadDefaultValues();

            if ($parent instanceof Section) {
                $asset->populateSectionRelation($parent);
            } else {
                $asset->populateEntryRelation($parent);
            }

            $asset->populateFileRelation($file);
            $asset->insert();

            $this->errorOrSuccess($asset, Yii::t('cms', 'ASSET_SUCCESS_CREATED'));
        }

        $provider = Yii::$container->get(FileActiveDataProvider::class, config: [
            'folder' => Folder::findOne($folder),
            'search' => $q,
        ]);

        return $this->render('create', [
            'provider' => $provider,
            'parent' => $parent,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $asset = $this->findAsset($id, Asset::AUTH_ASSET_UPDATE);

        if ($asset->load($this->request->post()) && !$this->request->isFormReload()) {
            if ($asset->update()) {
                $this->success(Lang::t('cms', 'ASSET_SUCCESS_UPDATED'));
            }

            return $this->redirectToParent($asset);
        }

        return $this->render('update', [
            'asset' => $asset,
        ]);
    }

    public function actionDelete(int $id): Response|string
    {
        $asset = $this->findAsset($id, Asset::AUTH_ASSET_DELETE);

        $asset->delete();
        $this->errorOrSuccess($asset, Lang::t('cms', 'ASSET_SUCCESS_DELETED'));

        return $this->redirectToParent($asset);
    }

    public function actionDuplicate(int $id): Response|string
    {
        $asset = $this->findAsset($id, Asset::AUTH_ASSET_UPDATE);

        $duplicate = DuplicateAsset::create([
            'asset' => $asset,
        ]);

        if ($errors = $duplicate->getFirstErrors()) {
            $this->error($errors);
            return $this->redirect(['update', 'id' => $asset->id]);
        }

        $this->success(Lang::t('cms', 'ASSET_SUCCESS_DUPLICATED'));
        return $this->redirect(['update', 'id' => $duplicate->id]);
    }

    public function actionOrder(?int $entry = null, ?int $section = null): string
    {
        $parent = $this->findAssetParent(
            $entry,
            $section,
            Entry::AUTH_ENTRY_ASSET_ORDER,
            Section::AUTH_SECTION_ASSET_ORDER,
        );

        $success = ReorderAssets::runWithBodyParam('asset', [
            'parent' => $parent,
        ]);

        if ($success) {
            $this->success(Lang::t('cms', 'ASSET_SUCCESS_ORDERED'));
        }

        return (string)Flashes::make();
    }

    protected function redirectToParent(Asset $asset): Response
    {
        $parent = $asset->parent;

        return $this->redirect([
            '/admin/cms/asset/index',
            $parent->getParamName() => $parent->id,
            '#' => "asset-$asset->id",
        ]);
    }

    protected function findAssetParent(
        ?int $entry,
        ?int $section,
        string $entryPermission,
        string $sectionPermission
    ): Entry|Section {
        if ($section) {
            return $this->findSection($section, $sectionPermission);
        }

        if ($entry) {
            return $this->findEntry($entry, $entryPermission);
        }

        throw new NotFoundHttpException();
    }
}
