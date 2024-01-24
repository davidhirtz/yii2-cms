<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\actions\ReorderCategories;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\CategoryTrait;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\web\Controller;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class CategoryController extends Controller
{
    use CategoryTrait;
    use ModuleTrait;

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
        ];
    }

    public function actionIndex(?int $id = null, ?string $q = null): Response|string
    {
        $provider = Yii::$container->get(CategoryActiveDataProvider::class, [], [
            'category' => $id ? Category::findOne($id) : null,
            'searchString' => $q,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(?int $id = null): Response|string
    {
        $category = Category::create();
        $category->loadDefaultValues();
        $category->parent_id = $id;

        if (!Yii::$app->getUser()->can('categoryCreate', ['category' => $category])) {
            throw new ForbiddenHttpException();
        }

        if ($category->load(Yii::$app->getRequest()->post()) && $category->insert()) {
            $this->success(Yii::t('cms', 'The category was created.'));
            return $this->redirect(['update', 'id' => $category->id]);
        }

        return $this->render('create', [
            'category' => $category,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $category = $this->findCategory($id, 'categoryUpdate');

        if ($category->load(Yii::$app->getRequest()->post())) {
            if ($category->update()) {
                $this->success(Yii::t('cms', 'The category was updated.'));
            }

            if (!$category->hasErrors()) {
                return $this->redirect(['update', 'id' => $category->id]);
            }
        }

        $provider = Yii::$container->get(CategoryActiveDataProvider::class, [], [
            'category' => $category,
        ]);

        return $this->render('update', [
            'provider' => $provider,
            'category' => $category,
        ]);
    }

    public function actionDelete(int $id): Response|string
    {
        $category = $this->findCategory($id, 'categoryDelete');

        if ($category->delete()) {
            $this->success(Yii::t('cms', 'The category was deleted.'));
            return $this->redirect(['index']);
        }

        $errors = $category->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    public function actionOrder(?int $id = null): void
    {
        ReorderCategories::runWithBodyParam('category', [
            'parent' => $id ? $this->findCategory($id, 'categoryOrder') : null,
        ]);
    }
}
