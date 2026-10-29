<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Skeleton\Web\Traits\StatusControllerTrait;
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
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CategoryController extends AbstractController
{
    use CategoryControllerTrait;
    use StatusControllerTrait;

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
                        'actions' => ['create', 'delete', 'index', 'reorder', 'status', 'update'],
                        'roles' => [Category::AUTH_CATEGORY],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'reorder' => ['post'],
                    'status' => ['post'],
                ],
            ],
        ];
    }

    #[Override]
    public function beforeAction($action): bool
    {
        if (!static::getModule()->enableCategories) {
            throw new NotFoundHttpException();
        }

        return parent::beforeAction($action);
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

    public function actionCreate(?int $parent = null, ?int $type = null): Response|string
    {
        $category = Category::instantiateFromPost($this->request->post(), $type);
        $category->loadDefaultValues();
        $category->parent_id = $parent;

        if (!$this->webuser->can(Category::AUTH_CATEGORY)) {
            throw new ForbiddenHttpException();
        }

        if ($category->load($this->request->post()) && !$this->request->isFormReload() && $category->insert()) {
            $this->success(Yii::t('cms', 'CATEGORY_CREATE_SUCCESS'));
            return $this->redirect(['index', 'parent' => $category->parent_id]);
        }

        return $this->render('create', [
            'category' => $category,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $category = $this->findCategory($id);

        if ($category->load($this->request->post()) && !$this->request->isFormReload()) {
            if ($category->update()) {
                $this->success(Yii::t('cms', 'CATEGORY_UPDATE_SUCCESS'));
            }

            if (!$category->hasErrors()) {
                return $this->redirect(['index', 'parent' => $category->parent_id]);
            }
        }

        return $this->render('update', [
            'category' => $category,
        ]);
    }

    public function actionStatus(int $id): Response
    {
        return $this->updateStatus($this->findCategory($id));
    }

    public function actionDelete(int $id): Response|string
    {
        $category = $this->findCategory($id);

        if ($category->delete()) {
            $this->success(Yii::t('cms', 'CATEGORY_DELETE_SUCCESS'));
        } elseif ($errors = $category->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect(['index', 'parent' => $category->parent_id]);
    }

    public function actionReorder(?int $id = null): string
    {
        $success = ReorderCategories::runWithBodyParam('category', [
            'parent' => $id ? $this->findCategory($id) : null,
        ]);

        if ($success) {
            $this->success(Yii::t('cms', 'CATEGORY_ORDER_SUCCESS'));
        }

        return (string) Flashes::make();
    }
}
