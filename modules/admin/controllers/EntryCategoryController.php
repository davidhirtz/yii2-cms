<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

/**
 * Class EntryCategoryController.
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class EntryCategoryController extends Controller
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
                        'actions' => ['create', 'index', 'order', 'delete'],
                        'roles' => ['author'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'order' => ['post'],
                    'create' => ['post'],
                ],
            ],
        ]);
    }

    /**
     * @param int $entry
     * @param string $q
     * @return string
     */
    public function actionIndex($entry = null, $q = null)
    {
        if (!$entry = Entry::findOne($entry)) {
            throw new NotFoundHttpException;
        }

        $provider = new CategoryActiveDataProvider([
            'entry' => $entry,
            'searchString' => $q,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int $entry
     * @param int $category
     * @return string|\yii\web\Response
     */
    public function actionCreate($entry, $category)
    {
        $junction = new EntryCategory([
            'entry_id' => $entry,
            'category_id' => $category,
        ]);

        $junction->insert();
        return $this->redirect(['index', 'entry' => $junction->entry_id]);
    }

    /**
     * @param int $entry
     * @param int $category
     * @return string|\yii\web\Response
     */
    public function actionDelete($entry, $category)
    {
        if (!$junction = EntryCategory::findOne(['entry_id' => $entry, 'category_id' => $category])) {
            throw new NotFoundHttpException;
        }

        $junction->delete();
        return $this->redirect(['index', 'entry' => $junction->entry_id]);
    }

    /**
     * @param int $category
     */
    public function actionOrder($category)
    {
        $entries = EntryCategory::find()->select(['entry_id', 'category_id', 'position'])
            ->where(['category_id' => $category])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        EntryCategory::updatePosition($entries, array_flip(Yii::$app->getRequest()->post('entry')));
    }
}