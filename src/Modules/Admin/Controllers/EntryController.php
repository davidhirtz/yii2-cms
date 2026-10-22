<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Actions\DuplicateEntry;
use Hirtz\Cms\Models\Actions\ReorderEntries;
use Hirtz\Cms\Models\Actions\ReplaceIndexEntry;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Web\Traits\StatusControllerTrait;
use Hirtz\Skeleton\Widgets\Flashes;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EntryController extends AbstractController
{
    use EntryControllerTrait;
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
                        'actions' => [
                            'create',
                            'delete',
                            'duplicate',
                            'index',
                            'order',
                            'replace-index',
                            'status',
                            'update',
                            'update-all',
                        ],
                        'roles' => [Entry::AUTH_ENTRY],
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
                    'status' => ['post'],
                    'update-all' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(
        ?int $category = null,
        ?int $parent = null,
        ?int $type = null,
        ?string $q = null,
        ?int $status = null,
    ): Response|string {
        if (!$type && static::getModule()->defaultEntryType) {
            return $this->redirect(Url::current(['type' => static::getModule()->defaultEntryType]));
        }

        $provider = Yii::$container->get(EntryActiveDataProvider::class, config: [
            'category' => Category::findOne($category),
            'parent' => Entry::findOne($parent),
            'searchString' => $q,
            'status' => $status,
            'type' => $type,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(?int $parent = null, ?int $type = null): Response|string
    {
        $entry = Entry::instantiateFromPost($this->request->post(), $type ?: static::getModule()->defaultEntryType);
        $entry->loadDefaultValues();

        $parent = Entry::findOne($parent);
        $entry->populateParentRelation($parent);

        if ($parent) {
            $entry->tenant_id = $parent->tenant_id;
        }

        if (!$this->webuser->can(Entry::AUTH_ENTRY)) {
            throw new ForbiddenHttpException();
        }

        if ($entry->load($this->request->post()) && !$this->request->isFormReload()) {
            if ($entry->isNewIndex()) {
                foreach ($entry->getI18nAttributesNames('slug') as $attribute) {
                    $entry->$attribute = (string)static::getModule()->entryIndexSlug;
                }
            }

            if ($entry->insert()) {
                $this->success(Yii::t('cms', 'ENTRY_SUCCESS_CREATED'));
                return $this->redirectToEntry($entry);
            }
        }

        return $this->render('create', [
            'entry' => $entry,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $entry = $this->findEntry($id);

        if ($entry->load($this->request->post()) && !$this->request->isFormReload()) {
            if ($entry->update()) {
                $this->success(Yii::t('cms', 'ENTRY_SUCCESS_UPDATED'));
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
        if ($entryIds = array_map(intval(...), $this->request->post('selection', []))) {
            $entries = Entry::findAll(['id' => $entryIds]);
            $isUpdated = false;

            foreach ($entries as $entry) {
                if ($this->webuser->can(Entry::AUTH_ENTRY)) {
                    if ($entry->load($this->request->post())) {
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
                $this->success(Yii::t('cms', 'ENTRY_SUCCESS_SELECTED_UPDATED'));
            }
        }

        return $this->redirect($this->request->getReferrer() ?? ['index']);
    }

    public function actionDuplicate(int $id): Response|string
    {
        $entry = $this->findEntry($id);

        $duplicate = DuplicateEntry::create([
            'entry' => $entry,
        ]);

        if ($errors = $duplicate->getFirstErrors()) {
            $this->error($errors);
        } else {
            $this->success(Yii::t('cms', 'ENTRY_SUCCESS_DUPLICATED'));
        }

        return $this->redirect(['update', 'id' => $duplicate->id ?? $entry->id]);
    }

    public function actionReplaceIndex(int $id): Response|string
    {
        $entry = $this->findEntry($id);

        if (!$entry->allowsReplaceIndex()) {
            throw new NotFoundHttpException();
        }

        $index = Entry::find()->whereIndex($entry->tenant)->one();

        $action = Yii::createObject(ReplaceIndexEntry::class, [
            'entry' => $entry,
            'previous' => $index,
        ]);

        if ($action->replaceIndexEntry()) {
            $this->success(Yii::t('cms', 'ENTRY_SUCCESS_UPDATED'));
        } else {
            $this->error($index?->hasErrors() ? $index : $entry);
        }

        return $this->redirectToEntry($entry);
    }

    public function actionStatus(int $id): Response
    {
        return $this->updateStatus($this->findEntry($id));
    }

    public function actionDelete(int $id): Response|string
    {
        $entry = $this->findEntry($id);

        if ($entry->delete()) {
            $this->success(Yii::t('cms', 'ENTRY_SUCCESS_DELETED'));
        } elseif ($errors = $entry->getFirstErrors()) {
            $this->error($errors);
        }

        // Back to the list it was in: a type-less one would redirect to the default type, which may not be its own.
        return $this->redirect([
            'index',
            'parent' => $entry->parent_id,
            'type' => static::getModule()->defaultEntryType ? $entry->type : null,
        ]);
    }

    public function actionOrder(?int $parent = null, ?int $tenant = null): string
    {
        $success = ReorderEntries::runWithBodyParam('entry', [
            'parent' => $parent ? $this->findEntry($parent) : null,
            'tenantId' => $this->getTenantId($tenant),
        ]);

        if ($success) {
            $this->success(Yii::t('cms', 'ENTRY_SUCCESS_ORDERED'));
        }

        return (string)Flashes::make();
    }

    protected function redirectToEntry(Entry $entry): Response
    {
        $route = $entry->getAdminRoute();

        // An integer query key would be renumbered ahead of the route and taken for it
        return $this->redirect($route
            ? [...array_filter($this->request->get(), is_string(...), ARRAY_FILTER_USE_KEY), ...$route]
            : ['index', ...$this->request->get()]);
    }
}
