<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class CategoryController.
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class CategoryController extends Controller
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
     * @param int $id
     * @param string $q
     * @return string
     */
    public function actionIndex($id = null, $q = null)
    {
        $provider = new CategoryActiveDataProvider([
            'category' => $id ? Category::findOne((int)$id) : null,
            'searchString' => $q,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionCreate($id = null)
    {
        $category = new Category();
        $category->parent_id = $id;

        if ($category->load(Yii::$app->getRequest()->post()) && $category->insert()) {
            $this->success(Yii::t('cms', 'The category was created.'));
            return $this->redirect(['update', 'id' => $category->id]);
        }

        /** @noinspection MissedViewInspection */
        return $this->render('create', [
            'category' => $category,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        if (!$category = Category::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if ($category->load(Yii::$app->getRequest()->post())) {

            if ($category->update()) {
                $this->success(Yii::t('cms', 'The category was updated.'));
            }

            if (!$category->hasErrors()) {
                return $this->redirect(['index', 'id' => $category->parent_id]);
            }
        }

        $provider = new CategoryActiveDataProvider([
            'category' => $category,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'provider' => $provider,
            'category' => $category,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionDelete($id)
    {
        if (!$category = Category::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if ($category->delete()) {
            $this->success(Yii::t('cms', 'The category was deleted.'));
            return $this->redirect(['index']);
        }

        $errors = $category->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    /**
     * @param int $id
     */
    public function actionOrder($id=null)
    {
        $order=array_flip(Yii::$app->getRequest()->post('category'));
        $transaction=Yii::$app->getDb()->beginTransaction();

        try
        {
            Category::rebuildNestedTree(Category::findOne((int)$id), $order);
            $transaction->commit();
        }
        catch(\Exception $exception)
        {
            $transaction->rollBack();
            throw $exception;
        }
    }
}