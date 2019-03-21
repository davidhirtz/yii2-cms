<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\modules\admin\models\forms\AssetForm;
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
 *
 * @property \davidhirtz\yii2\cms\modules\admin\Module $module
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
     * @param int $id
     * @param int $type
     * @param string $q
     * @return string
     */
//    public function actionIndex($id = null, $type = null, $q = null)
//    {
//        $entry = $id ? MediaForm::findOne($id) : null;
//
//        $query = $this->getQuery()
//            ->andFilterWhere(['type' => $type])
//            ->orderBy(['position' => SORT_ASC])
//            ->matching($q);
//
//        if ($this->getModule()->defaultMediaOrderBy) {
//            $query->orderBy($this->getModule()->defaultMediaOrderBy);
//        }
//
//        if ($entry) {
//            $query->orderBy($entry->getOrderBy());
//        }
//
//        $provider = new MediaActiveDataProvider([
//            'query' => $query,
//        ]);
//
//        /** @noinspection MissedViewInspection */
//        return $this->render('index', [
//            'provider' => $provider,
//            'entry' => $entry,
//        ]);
//    }

    /**
     * @param int $entry
     * @param int $section
     * @param int $file
     * @return string|\yii\web\Response
     */
    public function actionCreate($entry = null, $section = null, $file = null)
    {
        $file = $file ? FileForm::findOne($file) : new FileForm;

        if ($file->getIsNewRecord()) {
            if (!$file->insert()) {
                $errors = $file->getFirstErrors();
                throw new ServerErrorHttpException(reset($errors));
            }
        }

        $asset = new Asset;
        $asset->entry_id = $entry;
        $asset->section_id = $section;
        $asset->file_id = $file->id;
        $asset->insert();

        if (Yii::$app->getRequest()->getIsAjax()) {
            return '';
        }

        $this->success(Yii::t('cms', 'The asset was created.'));
        return $this->redirect(['index']);
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
            return $this->refresh();
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
                return $this->asJson([]);
            }

            $this->success(Yii::t('cms', 'The asset was deleted.'));
            return $this->redirect([strtolower($asset->getParent()->formName()) . '/update', 'id' => $asset->getParent()->id]);
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
            $asset = Asset::find()->select(['id', 'position'])
                ->andWhere($entry ? ['entry_id' => $entry, 'section_id' => null] : ['section_id' => $section])
                ->orderBy(['position' => SORT_ASC])
                ->all();

            Asset::updatePosition($asset, array_flip(Yii::$app->getRequest()->post('media')));
        }
    }
}