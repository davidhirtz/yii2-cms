<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class EntryController.
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class EntryController extends Controller
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
    public function actionIndex($id = null, $type = null, $q = null)
    {
        $provider = new EntryActiveDataProvider([
            'entry' => $id ? EntryForm::findOne($id) : null,
            'searchString' => $q,
            'type' => $type,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int $id
     * @param int $type
     * @return string|\yii\web\Response
     */
    public function actionCreate($id = null, $type = null)
    {
        $entry = new EntryForm;

        $entry->parent_id = $id;
        $entry->type = $type;

        if ($entry->load(Yii::$app->getRequest()->post()) && $entry->insert()) {
            $this->success(Yii::t('cms', 'The entry was created.'));
            return $this->redirect(['update', 'id' => $entry->id]);
        }

        /** @noinspection MissedViewInspection */
        return $this->render('create', [
            'entry' => $entry,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        if (!$entry = EntryForm::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($entry->load(Yii::$app->getRequest()->post())) {

            if ($entry->update()) {
                $this->success(Yii::t('cms', 'The entry was updated.'));
            }

            if (!$entry->hasErrors()) {
                return $this->redirect(['index', 'id' => $entry->parent_id]);
            }
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'entry' => $entry,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionDelete($id)
    {
        if (!$entry = Entry::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($entry->delete()) {
            $this->success(Yii::t('cms', 'The entry was deleted.'));
            return $this->redirect(['index']);
        }

        $errors = $entry->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    /**
     * @param int $id
     */
    public function actionOrder($id = null)
    {
        $entries = Entry::find()->select(['id', 'position'])
            ->filterWhere(['parent_id' => $id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        Entry::updatePosition($entries, array_flip(Yii::$app->getRequest()->post('entry')));
    }
}