<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Skeleton\Widgets\Flashes;
use Hirtz\Cms\Models\Actions\ReorderCategories;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\CategoryControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class CategoryController extends AbstractController
{
    use CategoryControllerTrait;

    #[Override]
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
                        'roles' => [Category::AUTH_CATEGORY_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => [Category::AUTH_CATEGORY_CREATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [Category::AUTH_CATEGORY_DELETE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => [Category::AUTH_CATEGORY_ORDER],
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

    public function actionIndex(?int $parent = null, ?int $type = null, ?string $q = null): Response|string
    {
        $provider = Yii::$container->get(CategoryActiveDataProvider::class, config: [
            'parent' => Category::findOne($parent),
            'searchString' => $q,
            'type' => $type,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(?int $parent = null): Response|string
    {
        $category = Category::create();
        $category->loadDefaultValues();
        $category->parent_id = $parent;

        if (!$this->webuser->can(Category::AUTH_CATEGORY_CREATE, ['category' => $category])) {
            throw new ForbiddenHttpException();
        }

        if ($category->load($this->request->post()) && !$this->request->isFormReload() && $category->insert()) {
            $this->success(Yii::t('cms', 'CATEGORY_SUCCESS_CREATED'));
            return $this->redirect(['index', 'parent' => $category->parent_id]);
        }

        return $this->render('create', [
            'category' => $category,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $category = $this->findCategory($id, Category::AUTH_CATEGORY_UPDATE);

        if ($category->load($this->request->post()) && !$this->request->isFormReload()) {
            if ($category->update()) {
                $this->success(Yii::t('cms', 'CATEGORY_SUCCESS_UPDATED'));
            }

            if (!$category->hasErrors()) {
                return $this->redirect(['index', 'parent' => $category->parent_id]);
            }
        }

        return $this->render('update', [
            'category' => $category,
        ]);
    }

    public function actionDelete(int $id): Response|string
    {
        $category = $this->findCategory($id, Category::AUTH_CATEGORY_DELETE);

        if ($category->delete()) {
            $this->success(Yii::t('cms', 'CATEGORY_SUCCESS_DELETED'));
            return $this->redirect(['index', 'parent' => $category->parent_id]);
        }

        $errors = $category->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    public function actionOrder(?int $id = null): string
    {
        $success = ReorderCategories::runWithBodyParam('category', [
            'parent' => $id ? $this->findCategory($id, Category::AUTH_CATEGORY_ORDER) : null,
        ]);

        if ($success) {
            $this->success(Yii::t('cms', 'CATEGORY_SUCCESS_ORDERED'));
        }

        return (string) Flashes::make();
    }
}
