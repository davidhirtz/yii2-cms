<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\actions\DuplicateEntry;
use davidhirtz\yii2\cms\models\actions\ReorderEntries;
use davidhirtz\yii2\cms\models\actions\ReplaceIndexEntry;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\EntryTrait;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class EntryController extends AbstractController
{
    use EntryTrait;

    protected array|false|null $i18nTablesRoute = ['/admin/entry/index'];

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

        $provider = Yii::$container->get(EntryActiveDataProvider::class, [], [
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
            $this->success(Yii::t('cms', 'The entry was created.'));
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
                $this->success(Yii::t('cms', 'The entry was updated.'));
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

        if ($entryIds = array_map('intval', $request->post('selection', []))) {
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
                $this->success(Yii::t('cms', 'The selected entries were updated.'));
            }
        }

        return $this->redirect($request->get('redirect', array_merge($request->get(), ['index'])));
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
            $this->success(Yii::t('cms', 'The entry was duplicated.'));
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
            $this->success(Yii::t('cms', 'The entry was updated.'));
        }

        $this->error($entry);
        return $this->redirectToEntry($entry);
    }

    public function actionDelete(int $id): Response|string
    {
        $entry = $this->findEntry($id, Entry::AUTH_ENTRY_DELETE);

        if ($entry->delete()) {
            $this->success(Yii::t('cms', 'The entry was deleted.'));
        } elseif ($errors = $entry->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect(array_merge(Yii::$app->getRequest()->get(), ['index']));
    }

    public function actionOrder(?int $parent = null): void
    {
        ReorderEntries::runWithBodyParam('entry', [
            'parent' => $parent ? $this->findEntry($parent, Entry::AUTH_ENTRY_UPDATE) : null,
        ]);
    }

    protected function redirectToEntry(Entry $entry): Response
    {
        return $this->redirect(array_merge(Yii::$app->getRequest()->get(), ['update', 'id' => $entry->id]));
    }
}
