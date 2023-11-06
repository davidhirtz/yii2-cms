<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\CategoryTrait;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\EntryTrait;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\actions\ReorderEntryCategories;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class EntryCategoryController extends Controller
{
    use CategoryTrait;
    use EntryTrait;
    use ModuleTrait;

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'create', 'delete'],
                        'roles' => ['entryCategoryUpdate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => ['entryOrder'],
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

    public function actionIndex(int $entry, ?int $category = null, ?string $q = null): string
    {
        $entry = $this->findEntry($entry, 'entryUpdate');

        $provider = Yii::$container->get(CategoryActiveDataProvider::class, [], [
            'entry' => $entry,
            'category' => Category::findOne($category),
            'searchString' => $q,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(int $entry, int $category): Response
    {
        $entryCategory = EntryCategory::create();
        $entryCategory->entry_id = $entry;
        $entryCategory->category_id = $category;

        if (!Yii::$app->getUser()->can('entryCategoryUpdate', ['entryCategory' => $entryCategory])) {
            throw new ForbiddenHttpException();
        }

        $entryCategory->insert();
        return $this->redirect(['index', 'entry' => $entryCategory->entry_id]);
    }

    public function actionDelete(int $entry, int $category): Response
    {
        $entryCategory = EntryCategory::findOne([
            'entry_id' => $entry,
            'category_id' => $category,
        ]);

        if (!Yii::$app->getUser()->can('entryCategoryUpdate', ['entryCategory' => $entryCategory])) {
            throw new ForbiddenHttpException();
        }

        $entryCategory->delete();
        return $this->redirect(['index', 'entry' => $entryCategory->entry_id]);
    }

    public function actionOrder(int $category): void
    {
        ReorderEntryCategories::runWithBodyParam('entry', [
            'category' => $this->findCategory($category, 'entryOrder'),
        ]);
    }
}