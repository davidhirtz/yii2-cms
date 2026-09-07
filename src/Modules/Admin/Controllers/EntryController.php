<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Actions\DuplicateEntry;
use Hirtz\Cms\Models\Actions\ReorderEntries;
use Hirtz\Cms\Models\Actions\ReplaceIndexEntry;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Widgets\Flashes;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class EntryController extends AbstractController
{
    use EntryControllerTrait;

    protected array|false|null $i18nTablesRoute = ['/admin/cms/entry/index'];

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
                        'actions' => ['index', 'replace-index', 'update', 'update-all'],
                        'roles' => [Entry::AUTH_ENTRY_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['duplicate', 'create'],
                        'roles' => [Entry::AUTH_ENTRY_CREATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [Entry::AUTH_ENTRY_DELETE],
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
                    'duplicate' => ['post'],
                    'replace-index' => ['post'],
                    'order' => ['post'],
                    'update-all' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(
        ?int $category = null,
        ?int $parent = null,
        ?int $type = null,
        ?string $q = null
    ): Response|string {
        if (!$type && static::getModule()->defaultEntryType) {
            return $this->redirect(Url::current(['type' => static::getModule()->defaultEntryType]));
        }

        $provider = Yii::$container->get(EntryActiveDataProvider::class, config: [
            'category' => Category::findOne($category),
            'parent' => Entry::findOne($parent),
            'searchString' => $q,
            'type' => $type,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(?int $parent = null, ?int $type = null): Response|string
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->populateParentRelation(Entry::findOne($parent));
        $entry->type = $type ?: static::getModule()->defaultEntryType;

        $request = Yii::$app->getRequest();

        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_CREATE, ['entry' => $entry])) {
            throw new ForbiddenHttpException();
        }

        if ($entry->load($request->post()) && $entry->insert()) {
            $this->success(Lang::t('cms', 'ENTRY_SUCCESS_CREATED'));
            return $this->redirectToEntry($entry);
        }

        return $this->render('create', [
            'entry' => $entry,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $entry = $this->findEntry($id, Entry::AUTH_ENTRY_UPDATE);
        $request = Yii::$app->getRequest();

        if ($entry->load($request->post())) {
            if ($entry->update()) {
                $this->success(Lang::t('cms', 'ENTRY_SUCCESS_UPDATED'));
            }

            if (!$entry->hasErrors()) {
                return $this->redirectToEntry($entry);
            }
        }

        return $this->render('update', [
            'entry' => $entry,
        ]);
    }

    public function actionUpdateAll(): Response|string
    {
        $request = Yii::$app->getRequest();

        if ($entryIds = array_map(intval(...), $request->post('selection', []))) {
            $entries = Entry::findAll(['id' => $entryIds]);
            $isUpdated = false;

            foreach ($entries as $entry) {
                if (Yii::$app->getUser()->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $entry])) {
                    if ($entry->load($request->post())) {
                        if ($entry->update()) {
                            $isUpdated = true;
                        }

                        if ($entry->hasErrors()) {
                            $this->error($entry->getFirstErrors());
                        }
                    }
                }
            }

            if ($isUpdated) {
                $this->success(Lang::t('cms', 'ENTRY_SUCCESS_SELECTED_UPDATED'));
            }
        }

        return $this->redirect($request->getReferrer() ?? ['index']);
    }

    public function actionDuplicate(int $id): Response|string
    {
        $entry = $this->findEntry($id, Entry::AUTH_ENTRY_UPDATE);

        $duplicate = DuplicateEntry::create([
            'entry' => $entry,
        ]);

        if ($errors = $duplicate->getFirstErrors()) {
            $this->error($errors);
        } else {
            $this->success(Lang::t('cms', 'ENTRY_SUCCESS_DUPLICATED'));
        }

        return $this->redirect(['update', 'id' => $duplicate->id ?? $entry->id]);
    }

    public function actionReplaceIndex(int $id): Response|string
    {
        $permissionName = Entry::AUTH_ENTRY_UPDATE;

        $entry = $this->findEntry($id, $permissionName);
        $index = Entry::find()->whereIndex()->one();

        if ($index && !Yii::$app->getUser()->can($permissionName, ['entry' => $index])) {
            throw new ForbiddenHttpException();
        }

        ReplaceIndexEntry::run([
            'entry' => $entry,
            'previous' => $index,
        ]);

        if ($entry->isIndex()) {
            $this->success(Lang::t('cms', 'ENTRY_SUCCESS_UPDATED'));
        }

        $this->error($entry);
        return $this->redirectToEntry($entry);
    }

    public function actionDelete(int $id): Response|string
    {
        $entry = $this->findEntry($id, Entry::AUTH_ENTRY_DELETE);

        if ($entry->delete()) {
            $this->success(Lang::t('cms', 'ENTRY_SUCCESS_DELETED'));
        } elseif ($errors = $entry->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect([...Yii::$app->getRequest()->get(), 'index']);
    }

    public function actionOrder(?int $parent = null): string
    {
        $success = ReorderEntries::runWithBodyParam('entry', [
            'parent' => $parent ? $this->findEntry($parent, Entry::AUTH_ENTRY_UPDATE) : null,
        ]);

        if ($success) {
            $this->success(Lang::t('cms', 'ENTRY_SUCCESS_ORDERED'));
        }

        return (string) Flashes::make();
    }

    protected function redirectToEntry(Entry $entry): Response
    {
        return $this->redirect([...Yii::$app->getRequest()->get(), 'update', 'id' => $entry->id]);
    }
}
