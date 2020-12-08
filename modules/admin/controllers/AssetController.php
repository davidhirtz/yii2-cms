<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\AssetTrait;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\EntryTrait;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\SectionTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\modules\admin\data\FileActiveDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Class AssetController
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class AssetController extends Controller
{
    use AssetTrait;
    use EntryTrait;
    use SectionTrait;
    use ModuleTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'update'],
                        'roles' => ['entryAssetUpdate', 'sectionAssetUpdate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => ['entryAssetCreate', 'sectionAssetCreate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['entryAssetDelete', 'sectionAssetDelete'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => ['entryAssetOrder', 'sectionAssetOrder'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'order' => ['post'],
                ],
            ],
        ]);
    }

    /**
     * @param int|null $entry
     * @param int|null $section
     * @param int|null $folder
     * @param int|null $type
     * @param string|null $q
     * @return string
     */
    public function actionIndex($entry = null, $section = null, $folder = null, $type = null, $q = null)
    {
        $parent = $section ? $this->findSection($section, 'sectionAssetUpdate') :
            $this->findEntry($entry, 'entryAssetUpdate');

        if ($parent instanceof Entry) {
            // Populate assets without sections for file grid
            $parent->populateRelation('assets', $parent->getAssets()
                ->withoutSections()
                ->all());
        }

        /** @var FileActiveDataProvider $provider */
        $provider = Yii::createObject([
            'class' => 'davidhirtz\yii2\media\modules\admin\data\FileActiveDataProvider',
            'folderId' => $folder,
            'type' => $type,
            'search' => $q,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
            'parent' => $parent,
        ]);
    }

    /**
     * @param int|null $entry
     * @param int|null $section
     * @param int|null $file
     * @param int|null $folder
     * @return string|Response
     */
    public function actionCreate($entry = null, $section = null, $file = null, $folder = null)
    {
        $request = Yii::$app->getRequest();
        $user = Yii::$app->getUser();

        $file = $file ? File::findOne($file) : new File();
        $file->folder_id = $folder;

        if ($isNew = $file->getIsNewRecord()) {
            if (!$user->can('fileCreate', ['file' => $file])) {
                throw new ForbiddenHttpException();
            }

            // This is not very elegant right now. But copy errors need to be handled by validation
            // and upload errors might be a partial upload that should simply end the request.
            if ($url = $request->post('url')) {
                $file->copy($request->post('url'));
            } elseif (!$file->upload()) {
                return '';
            }

            if (!$file->insert()) {
                $errors = $file->getFirstErrors();
                throw new BadRequestHttpException(reset($errors));
            }
        }

        $asset = new Asset();
        $asset->entry_id = $entry;
        $asset->section_id = $section;
        $asset->file_id = $file->id;

        if (!$user->can($asset->isEntryAsset() ? 'entryAssetCreate' : 'sectionAssetCreate', ['asset' => $asset])) {
            throw new ForbiddenHttpException();
        }

        if (!$asset->insert()) {
            $errors = $asset->getFirstErrors();
            throw new BadRequestHttpException(reset($errors));
        }

        if ($request->getIsAjax()) {
            return '';
        }

        $this->success($isNew ? Yii::t('cms', 'The asset was created.') : Yii::t('cms', 'The asset was added.'));
        return $this->redirectToParent($asset);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate(int $id)
    {
        $asset = $this->findAsset($id);

        if (!Yii::$app->getUser()->can($asset->isEntryAsset() ? 'entryAssetUpdate' : 'sectionAssetUpdate', ['asset' => $asset])) {
            throw new ForbiddenHttpException();
        }

        if ($asset->load(Yii::$app->getRequest()->post())) {
            if ($asset->update()) {
                $this->success(Yii::t('cms', 'The asset was updated.'));
            }

            if (!$asset->hasErrors()) {
                return $this->redirectToParent($asset);
            }
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'asset' => $asset,
        ]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionDelete($id)
    {
        $asset = $this->findAsset($id);

        if (!Yii::$app->getUser()->can($asset->isEntryAsset() ? 'entryAssetDelete' : 'sectionAssetDelete', ['asset' => $asset])) {
            throw new ForbiddenHttpException();
        }

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

    /**
     * @param int|null $entry
     * @param int|null $section
     */
    public function actionOrder($entry = null, $section = null)
    {
        $parent = $section ? $this->findSection($section, 'sectionAssetOrder') :
            $this->findEntry($entry, 'entryAssetOrder');

        $assetIds = array_map('intval', array_filter(Yii::$app->getRequest()->post('asset', [])));

        if ($assetIds) {
            $parent->updateAssetOrder($assetIds);
        }
    }

    /**
     * @param Asset $asset
     * @param bool $isDeleted
     * @return Response
     */
    private function redirectToParent(Asset $asset, $isDeleted = false)
    {
        $route = $asset->section_id ? ['/admin/section/update', 'id' => $asset->section_id] : ['/admin/entry/update', 'id' => $asset->entry_id];
        return $this->redirect($route + ['#' => $isDeleted ? 'assets' : ('asset-' . $asset->id)]);
    }
}