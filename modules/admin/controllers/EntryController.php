<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 * Class EntryController.
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class EntryController extends Controller
{
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
                        'actions' => ['clone', 'create', 'delete', 'index', 'order', 'update'],
                        'roles' => ['author'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'clone' => ['post'],
                    'delete' => ['post'],
                    'order' => ['post'],
                ],
            ],
        ]);
    }

    /**
     * @param int $category
     * @param int $type
     * @param string $q
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
     * @param int $type
     * @return string|\yii\web\Response
     */
    public function actionCreate($type = null)
    {
        $entry = new Entry();
        $entry->type = $type ?: static::getModule()->defaultEntryType;
        $request = Yii::$app->getRequest();

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
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        $entry = $this->findEntry($id);
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
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionClone($id)
    {
        $entry = $this->findEntry($id);
        $clone = $entry->clone();

        if ($errors = $clone->getFirstErrors()) {
            $this->error($errors);

        } else {
            $this->success(Yii::t('cms', 'The entry was duplicated.'));
        }

        return $this->redirect(['update', 'id' => $clone->id ?: $entry->id]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionDelete($id)
    {
        $entry = $this->findEntry($id);

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

            Entry::updatePosition($entries, array_flip($entryIds));
        }
    }

    /**
     * @param int $id
     * @return Entry
     * @throws NotFoundHttpException
     */
    protected function findEntry($id)
    {
        if (!$entry = Entry::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        return $entry;
    }
}