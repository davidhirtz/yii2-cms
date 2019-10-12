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
use yii\web\ServerErrorHttpException;

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
                        'actions' => ['create', 'index', 'order', 'update', 'delete'],
                        'roles' => ['author'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'order' => ['post'],
                ],
            ],
        ]);
    }

    /**
     * @param int $id
     * @param int $category
     * @param int $type
     * @param string $q
     * @return string
     */
    public function actionIndex($id = null, $category = null, $type = null, $q = null)
    {
        if (!$type && static::getModule()->defaultEntryType) {
            return $this->redirect(Url::current(['type' => static::getModule()->defaultEntryType]));
        }

        $provider = new EntryActiveDataProvider([
            'category' => $category ? Category::findOne($category) : null,
            'entry' => $id ? Entry::findOne($id) : null,
            'searchString' => $q,
            'type' => $type,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int $id
     * @param int $type
     * @return string|\yii\web\Response
     */
    public function actionCreate($id = null, $type = null)
    {
        $entry = new Entry;
        $entry->type = $type ?: static::getModule()->defaultEntryType;
        $request = Yii::$app->getRequest();

        if (static::getModule()->enableNestedEntries) {
            $entry->parent_id = $id;
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
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        if (!$entry = Entry::findOne($id)) {
            throw new NotFoundHttpException;
        }

        $request = Yii::$app->getRequest();

        if ($entry->load($request->post())) {
            if ($entry->update()) {
                $this->success(Yii::t('cms', 'The entry was updated.'));
            }

            if (!$entry->hasErrors()) {
                return $this->redirect(array_merge($request->get(), ['update', 'id' => $entry->id]));
            }
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'entry' => $entry,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionDelete($id)
    {
        if (!$entry = Entry::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($entry->delete()) {
            $this->success(Yii::t('cms', 'The entry was deleted.'));
            return $this->redirect(array_merge(Yii::$app->getRequest()->get(), ['index']));
        }

        $errors = $entry->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    /**
     * @param int $id
     */
    public function actionOrder($id = null)
    {
        $entries = Entry::find()->select(['id', 'position'])
            ->filterWhere(['parent_id' => $id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        Entry::updatePosition($entries, array_flip(Yii::$app->getRequest()->post('entry')));
    }
}