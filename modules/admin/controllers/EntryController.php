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
 * Class EntryController
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class EntryController extends Controller
{
    use EntryTrait;
    use ModuleTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
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

    /**
     * @param int|null $category
     * @param int|null $type
     * @param string|null $q
     * @return string
     */
    public function actionIndex($category = null, $type = null, $q = null)
    {
        if (!$type && static::getModule()->defaultEntryType) {
            return $this->redirect(Url::current(['type' => static::getModule()->defaultEntryType]));
        }

        /** @var EntryActiveDataProvider $provider */
        $provider = Yii::createObject([
            'class' => 'davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider',
            'category' => $category ? Category::findOne((int)$category) : null,
            'searchString' => $q,
            'type' => $type,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int|null $type
     * @return string|Response
     */
    public function actionCreate($type = null)
    {
        $entry = new Entry();
        $entry->type = $type ?: static::getModule()->defaultEntryType;
        $request = Yii::$app->getRequest();

        if (!Yii::$app->getUser()->can('entryCreate', ['entry' => $entry])) {
            throw new ForbiddenHttpException();
        }

        if ($entry->load($request->post()) && $entry->insert()) {
            $this->success(Yii::t('cms', 'The entry was created.'));
            return $this->redirect(array_merge($request->get(), ['update', 'id' => $entry->id]));
        }

        /** @noinspection MissedViewInspection */
        return $this->render('create', [
            'entry' => $entry,
        ]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate(int $id)
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

        // Populate assets without sections for asset grid
        $entry->populateRelation('assets', $entry->getAssets()
            ->withoutSections()
            ->with(['file', 'file.folder'])
            ->all());

        /** @noinspection MissedViewInspection */
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

        return $this->redirect(array_merge($request->get(), ['index']));
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