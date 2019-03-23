<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\AssetForm;
use davidhirtz\yii2\media\modules\admin\data\FileActiveDataProvider;
use davidhirtz\yii2\media\modules\admin\models\forms\FileForm;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class AssetController.
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
                    'upload' => ['post'],
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
            if (!$parent = SectionForm::findOne($section)) {
                throw new NotFoundHttpException;
            }

        } elseif (!$entry || !$parent = EntryForm::findOne($entry)) {
            throw new NotFoundHttpException;
        }

        $provider = new FileActiveDataProvider([
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
     * @return string|\yii\web\Response
     */
    public function actionCreate($entry = null, $section = null, $file = null)
    {
        $file = $file ? FileForm::findOne($file) : new FileForm;
        $isNew = $file->getIsNewRecord();

        if ($file->getIsNewRecord()) {
            if (!$file->insert()) {
                $errors = $file->getFirstErrors();
                throw new ServerErrorHttpException(reset($errors));
            }
        }

        $asset = new AssetForm;
        $asset->entry_id = $entry;
        $asset->section_id = $section;
        $asset->file_id = $file->id;
        $asset->insert();

        if (Yii::$app->getRequest()->getIsAjax()) {
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
        if (!$asset = AssetForm::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($asset->load(Yii::$app->getRequest()->post()) && $asset->update()) {
            $this->success(Yii::t('cms', 'The asset was updated.'));
            return $this->redirectToParent($asset);
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
        if (!$asset = AssetForm::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($asset->delete()) {

            if (Yii::$app->getRequest()->getIsAjax()) {
                return '';
            }

            $this->success(Yii::t('cms', 'The asset was deleted.'));
            return $this->redirectToParent($asset);
        }

        $errors = $asset->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    /**
     * @param int $entry
     * @param int $section
     */
    public function actionOrder($entry = null, $section = null)
    {
        if ($entry || $section) {
            $asset = AssetForm::find()->select(['id', 'position'])
                ->andWhere($entry ? ['entry_id' => $entry, 'section_id' => null] : ['section_id' => $section])
                ->orderBy(['position' => SORT_ASC])
                ->all();

            AssetForm::updatePosition($asset, array_flip(Yii::$app->getRequest()->post('asset')));
        }
    }

    /**
     * @param AssetForm $asset
     * @return \yii\web\Response
     */
    private function redirectToParent(AssetForm $asset)
    {
        return $this->redirect(($asset->section_id ? ['/admin/section/update', 'id' => $asset->section_id] : ['/admin/entry/update', 'id' => $asset->entry_id]) + ['#' => 'assets']);
    }
}