<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\modules\admin\controllers\traits\CategoryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\web\Controller;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Class CategoryController
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class CategoryController extends Controller
{
    use CategoryTrait;
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
                        'actions' => ['index', 'update'],
                        'roles' => ['categoryUpdate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => ['categoryCreate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['categoryDelete'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => ['categoryOrder'],
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
     * @param int|null $id
     * @param string|null $q
     * @return string
     */
    public function actionIndex($id = null, $q = null)
    {
        /** @var CategoryActiveDataProvider $provider */
        $provider = Yii::createObject([
            'class' => 'davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider',
            'category' => $id ? Category::findOne((int)$id) : null,
            'searchString' => $q,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int|null $id
     * @return string|Response
     */
    public function actionCreate($id = null)
    {
        $category = new Category();
        $category->loadDefaultValues();
        $category->parent_id = $id;

        if (!Yii::$app->getUser()->can('categoryCreate', ['category' => $category])) {
            throw new ForbiddenHttpException();
        }

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
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        $category = $this->findCategory($id, 'categoryUpdate');

        if ($category->load(Yii::$app->getRequest()->post())) {
            if ($category->update()) {
                $this->success(Yii::t('cms', 'The category was updated.'));
            }

            if (!$category->hasErrors()) {
                return $this->redirect(['index', 'id' => $category->parent_id]);
            }
        }

        /** @var CategoryActiveDataProvider $provider */
        $provider = Yii::createObject([
            'class' => 'davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider',
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
     * @return string|Response
     */
    public function actionDelete(int $id)
    {
        $category = $this->findCategory($id, 'categoryDelete');

        if ($category->delete()) {
            $this->success(Yii::t('cms', 'The category was deleted.'));
            return $this->redirect(['index']);
        }

        $errors = $category->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    /**
     * @param int|null $id
     */
    public function actionOrder($id = null)
    {
        $category = Category::findOne((int)$id);
        $categoryIds = array_map('intval', array_filter(Yii::$app->getRequest()->post('category', [])));
        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
            Category::rebuildNestedTree($category, array_flip($categoryIds));
            Trail::createOrderTrail($category, Yii::t('cms', 'Category order changed'));
            $transaction->commit();
        } catch (Exception $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }
}