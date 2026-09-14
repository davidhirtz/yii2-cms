<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Skeleton\Widgets\Flashes;
use Hirtz\Cms\Models\Actions\ReorderEntryCategories;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\CategoryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EntryCategoryController extends AbstractController
{
    use CategoryControllerTrait;
    use EntryControllerTrait;

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
                        'actions' => ['create', 'delete', 'index', 'order'],
                        'roles' => [Entry::AUTH_ENTRY],
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
        ];
    }

    public function actionIndex(int $entry, ?int $category = null, ?string $q = null): string
    {
        $entry = $this->findEntry($entry);

        $provider = Yii::$container->get(CategoryActiveDataProvider::class, config: [
            'entry' => $entry,
            'parent' => Category::findOne($category),
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

        if (!$this->webuser->can(Entry::AUTH_ENTRY)) {
            throw new ForbiddenHttpException();
        }

        $entryCategory->insert();

        $this->errorOrSuccess($entryCategory, Yii::t('cms', 'ENTRY_CATEGORY_SUCCESS_LINKED', [
            'count' => $entryCategory->getAffectedCategoryCount(),
        ]));

        return $this->redirectToIndex($entryCategory);
    }

    public function actionDelete(int $entry, int $category): Response
    {
        $entryCategory = EntryCategory::findOne([
            'entry_id' => $entry,
            'category_id' => $category,
        ]);

        if (!$entryCategory) {
            throw new NotFoundHttpException();
        }

        if (!$this->webuser->can(Entry::AUTH_ENTRY)) {
            throw new ForbiddenHttpException();
        }

        $entryCategory->delete();

        $this->errorOrSuccess($entryCategory, Yii::t('cms', 'ENTRY_CATEGORY_SUCCESS_REMOVED', [
            'count' => $entryCategory->getAffectedCategoryCount(),
        ]));

        return $this->redirectToIndex($entryCategory);
    }

    protected function redirectToIndex(EntryCategory $entryCategory): Response
    {
        return $this->redirect([
            'index',
            ...$this->request->getQueryParams(),
            'entry' => $entryCategory->entry_id,
            'category' => null,
        ]);
    }

    public function actionOrder(int $category): string
    {
        $success = ReorderEntryCategories::runWithBodyParam('entry', [
            'category' => $this->findCategory($category),
        ]);

        if ($success) {
            $this->success(Yii::t('cms', 'ENTRY_SUCCESS_ORDERED'));
        }

        return (string) Flashes::make();
    }
}
