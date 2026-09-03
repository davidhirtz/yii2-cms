<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Skeleton\I18n\Lang;
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
                        'actions' => ['index', 'create', 'delete'],
                        'roles' => [Entry::AUTH_ENTRY_CATEGORY_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => [Entry::AUTH_ENTRY_ORDER],
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
        $entry = $this->findEntry($entry, Entry::AUTH_ENTRY_UPDATE);

        $provider = Yii::$container->get(CategoryActiveDataProvider::class, config: [
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

        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_CATEGORY_UPDATE, ['entryCategory' => $entryCategory])) {
            throw new ForbiddenHttpException();
        }

        $entryCategory->insert();
        $this->errorOrSuccess($entryCategory, Lang::t('cms', 'ENTRY_CATEGORY_SUCCESS_LINKED'));

        return $this->redirectToIndex($entryCategory);
    }

    public function actionDelete(int $entry, int $category): Response
    {
        $entryCategory = EntryCategory::findOne([
            'entry_id' => $entry,
            'category_id' => $category,
        ]);

        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_CATEGORY_UPDATE, ['entryCategory' => $entryCategory])) {
            throw new ForbiddenHttpException();
        }

        $entryCategory->delete();
        $this->errorOrSuccess($entryCategory, Lang::t('cms', 'ENTRY_CATEGORY_SUCCESS_REMOVED'));

        return $this->redirectToIndex($entryCategory);
    }

    protected function redirectToIndex(EntryCategory $entryCategory): Response
    {
        return $this->redirect([
            'index',
            ...Yii::$app->getRequest()->getQueryParams(),
            'entry' => $entryCategory->entry_id,
            'category' => null,
        ]);
    }

    public function actionOrder(int $category): void
    {
        ReorderEntryCategories::runWithBodyParam('entry', [
            'category' => $this->findCategory($category, Entry::AUTH_ENTRY_ORDER),
        ]);
    }
}
