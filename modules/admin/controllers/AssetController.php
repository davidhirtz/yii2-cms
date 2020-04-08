<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\modules\admin\data\FileActiveDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class AssetController
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class AssetController extends Controller
{
    use ModuleTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create', 'index', 'order', 'update', 'delete'],
                        'roles' => ['author'],
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
     * @param int $entry
     * @param int $section
     * @param int $folder
     * @param int $type
     * @param string $q
     * @return string
     */
    public function actionIndex($entry = null, $section = null, $folder = null, $type = null, $q = null)
    {
        if ($section) {
            if (!$parent = Section::findOne((int)$section)) {
                throw new NotFoundHttpException;
            }

        } elseif (!$entry || !$parent = Entry::findOne((int)$entry)) {
            throw new NotFoundHttpException;
        }

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
     * @param int $entry
     * @param int $section
     * @param int $file
     * @param int $folder
     * @return string|\yii\web\Response
     */
    public function actionCreate($entry = null, $section = null, $file = null, $folder = null)
    {
        $request = Yii::$app->getRequest();
        $file = $file ? File::findOne($file) : new File;
        $file->folder_id = $folder;

        $isNew = $file->getIsNewRecord();

        if ($file->getIsNewRecord()) {
            $file->copy($request->post('url')) || $file->upload();

            if (!$file->insert()) {
                $errors = $file->getFirstErrors();
                throw new BadRequestHttpException(reset($errors));
            }
        }

        $asset = new Asset;
        $asset->entry_id = $entry;
        $asset->section_id = $section;
        $asset->file_id = $file->id;

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
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        if (!$asset = Asset::findOne($id)) {
            throw new NotFoundHttpException;
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
     * @return string|\yii\web\Response
     */
    public function actionDelete($id)
    {
        if (!$asset = Asset::findOne($id)) {
            throw new NotFoundHttpException;
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
     * @param int $entry
     * @param int $section
     */
    public function actionOrder($entry = null, $section = null)
    {
        if ($entry || $section) {
            $asset = Asset::find()->select(['id', 'position'])
                ->andWhere($entry ? ['entry_id' => $entry, 'section_id' => null] : ['section_id' => $section])
                ->orderBy(['position' => SORT_ASC])
                ->all();

            Asset::updatePosition($asset, array_flip(Yii::$app->getRequest()->post('asset')));
        }
    }

    /**
     * @param Asset $asset
     * @param bool $isDeleted
     * @return \yii\web\Response
     */
    private function redirectToParent(Asset $asset, $isDeleted = false)
    {
        return $this->redirect(($asset->section_id ? ['/admin/section/update', 'id' => $asset->section_id] : ['/admin/entry/update', 'id' => $asset->entry_id]) + ['#' => $isDeleted ? 'assets' : ('asset-' . $asset->id)]);
    }
}