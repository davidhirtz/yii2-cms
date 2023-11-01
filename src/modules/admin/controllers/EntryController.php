<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\EntryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Admin CRUD actions for {@see Entry}.
 */
class EntryController extends Controller
{
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
                        'actions' => ['index', 'update', 'update-all'],
                        'roles' => ['entryUpdate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['clone', 'create'],
                        'roles' => ['entryCreate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['entryDelete'],
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
                    'clone' => ['post'],
                    'delete' => ['post'],
                    'order' => ['post'],
                    'update-all' => ['post'],
                ],
            ],
        ]);
    }

    public function actionIndex(?int $category = null, ?int $parent = null, ?int $type = null, ?string $q = null): Response|string
    {
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

        if (!Yii::$app->getUser()->can('entryCreate', ['entry' => $entry])) {
            throw new ForbiddenHttpException();
        }

        if ($entry->load($request->post()) && $entry->insert()) {
            $this->success(Yii::t('cms', 'The entry was created.'));
            return $this->redirect(array_merge($request->get(), ['update', 'id' => $entry->id]));
        }

        return $this->render('create', [
            'entry' => $entry,
        ]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate(int $id): Response|string
    {
        $entry = $this->findEntry($id, 'entryUpdate');
        $request = Yii::$app->getRequest();

        if ($entry->load($request->post())) {
            if ($entry->update()) {
                $this->success(Yii::t('cms', 'The entry was updated.'));
            }

            if (!$entry->hasErrors()) {
                return $this->redirect(array_merge($request->get(), ['update', 'id' => $entry->id]));
            }
        }

        return $this->render('update', [
            'entry' => $entry,
        ]);
    }

    /**
     * @return Response
     */
    public function actionUpdateAll()
    {
        $request = Yii::$app->getRequest();

        if ($entryIds = array_map('intval', $request->post('selection', []))) {
            $entries = Entry::findAll(['id' => $entryIds]);
            $isUpdated = false;

            foreach ($entries as $entry) {
                if (Yii::$app->getUser()->can('entryUpdate', ['entry' => $entry])) {
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

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionClone(int $id)
    {
        $entry = $this->findEntry($id, 'entryUpdate');
        $clone = $entry->clone();

        if ($errors = $clone->getFirstErrors()) {
            $this->error($errors);
        } else {
            $this->success(Yii::t('cms', 'The entry was duplicated.'));
        }

        return $this->redirect($clone->id ? ['update', 'id' => $clone->id] : ['index']);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionDelete(int $id)
    {
        $entry = $this->findEntry($id, 'entryDelete');

        if ($entry->delete()) {
            $this->success(Yii::t('cms', 'The entry was deleted.'));
        } elseif ($errors = $entry->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect(array_merge(Yii::$app->getRequest()->get(), ['index']));
    }

    /**
     * Order entries based on position.
     */
    public function actionOrder()
    {
        $entryIds = array_map('intval', array_filter(Yii::$app->getRequest()->post('entry', [])));

        if ($entryIds) {
            $entries = Entry::find()->select(['id', 'position'])
                ->where(['id' => $entryIds])
                ->orderBy(['position' => SORT_ASC])
                ->all();

            if (Entry::updatePosition($entries, array_flip($entryIds))) {
                Trail::createOrderTrail(null, Yii::t('cms', 'Entry order changed'));
            }
        }
    }
}