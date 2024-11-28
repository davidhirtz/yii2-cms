<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\actions\DuplicateAsset;
use davidhirtz\yii2\cms\models\actions\ReorderAssets;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\AssetTrait;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\EntryTrait;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\SectionTrait;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\models\Folder;
use davidhirtz\yii2\media\modules\admin\controllers\traits\FileTrait;
use davidhirtz\yii2\media\modules\admin\data\FileActiveDataProvider;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class AssetController extends AbstractController
{
    use AssetTrait;
    use EntryTrait;
    use SectionTrait;
    use FileTrait;

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
                        'roles' => ['entryAssetOrder', Section::AUTH_SECTION_ASSET_ORDER],
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

    public function actionIndex(
        ?int $entry = null,
        ?int $section = null,
        ?int $folder = null,
        ?string $q = null
    ): Response|string {
        $parent = $section
            ? $this->findSection($section, Section::AUTH_SECTION_ASSET_UPDATE)
            : $this->findEntry($entry, Entry::AUTH_ENTRY_ASSET_UPDATE);

        if ($parent instanceof Entry) {
            $parent->populateRelation('assets', $parent->getAssets()
                ->withoutSections()
                ->all());
        }

        $provider = Yii::$container->get(FileActiveDataProvider::class, [], [
            'folder' => Folder::findOne($folder),
            'search' => $q,
        ]);

        return $this->render('index', [
            'provider' => $provider,
            'parent' => $parent,
        ]);
    }

    public function actionCreate(
        ?int $entry = null,
        ?int $section = null,
        ?int $file = null,
        ?int $folder = null
    ): Response|string {
        $request = Yii::$app->getRequest();
        $user = Yii::$app->getUser();

        if (!($file = File::findOne($file) ?: $this->insertFileFromRequest($folder))) {
            return '';
        }

        $asset = Asset::create();
        $asset->loadDefaultValues();
        $asset->entry_id = $entry;
        $asset->section_id = $section;
        $asset->populateFileRelation($file);

        if (!$user->can($asset->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_CREATE : Section::AUTH_SECTION_ASSET_CREATE, ['asset' => $asset])) {
            throw new ForbiddenHttpException();
        }

        if (!$asset->insert()) {
            $errors = $asset->getFirstErrors();
            throw new BadRequestHttpException(reset($errors));
        }

        if ($request->getIsAjax()) {
            return '';
        }

        $this->success(Yii::t('cms', 'The asset was added.'));
        return $this->redirectToParent($asset);
    }

    public function actionUpdate(int $id): Response|string
    {
        $asset = $this->findAsset($id, Asset::AUTH_ASSET_UPDATE);

        if ($asset->load(Yii::$app->getRequest()->post())) {
            if ($asset->update()) {
                $this->success(Yii::t('cms', 'The asset was updated.'));
            }

            if (!$asset->hasErrors()) {
                return $this->redirectToParent($asset);
            }
        }

        return $this->render('update', [
            'asset' => $asset,
        ]);
    }

    public function actionDelete(int $id): Response|string
    {
        $asset = $this->findAsset($id, Asset::AUTH_ASSET_DELETE);

        if ($asset->delete()) {
            if (Yii::$app->getRequest()->getIsAjax()) {
                return '';
            }

            $this->success(Yii::t('cms', 'The asset was deleted.'));
            return $this->redirectToParent($asset, true);
        }

        $errors = $asset->getFirstErrors();
        throw new BadRequestHttpException(reset($errors));
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

        $this->success(Yii::t('cms', 'The asset was duplicated.'));
        return $this->redirect(['update', 'id' => $duplicate->id]);
    }

    public function actionOrder(?int $entry = null, ?int $section = null): void
    {
        $parent = $section
            ? $this->findSection($section, Section::AUTH_SECTION_ASSET_ORDER)
            : $this->findEntry($entry, 'entryAssetOrder');

        ReorderAssets::runWithBodyParam('asset', [
            'parent' => $parent,
        ]);
    }

    private function redirectToParent(Asset $asset, bool $isDeleted = false): Response
    {
        $route = $asset->section_id ? ['/admin/section/update', 'id' => $asset->section_id] : ['/admin/entry/update', 'id' => $asset->entry_id];
        return $this->redirect($route + ['#' => $isDeleted ? 'assets' : ('asset-' . $asset->id)]);
    }
}
