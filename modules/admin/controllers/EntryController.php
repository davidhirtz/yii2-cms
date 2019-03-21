<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\data\Sort;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class BaseEntryController.
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 * @see EntryController
 *
 * @property \davidhirtz\yii2\cms\modules\admin\Module $module
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
                    'upload' => ['post'],
                ],
            ],
        ]);
    }

    /**
     * @param int $id
     * @param int $type
     * @param string $q
     * @return string
     */
    public function actionIndex($id = null, $type = null, $q = null)
    {
        $entry = $id ? EntryForm::findOne($id) : null;

        $query = $this->getQuery()
            ->andFilterWhere(['type' => $type])
            ->orderBy(['position' => SORT_ASC])
            ->matching($q);

        if ($this->getModule()->defaultEntryOrderBy) {
            $query->orderBy($this->getModule()->defaultEntryOrderBy);
        }

        if ($entry) {
            $query->orderBy($entry->getOrderBy());
        }

        $provider = new EntryActiveDataProvider([
            'query' => $query,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
            'entry' => $entry,
        ]);
    }

    /**
     * @param int $id
     * @param int $type
     * @return string|\yii\web\Response
     */
    public function actionCreate($id = null, $type = null)
    {
        $entry = new EntryForm;

        $entry->parent_id = $id;
        $entry->type = $type;

        if ($entry->load(Yii::$app->getRequest()->post()) && $entry->insert()) {
            $this->success(Yii::t('cms', 'The entry was created.'));
            return $this->redirect(['index']);
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
        if (!$entry = EntryForm::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($entry->load(Yii::$app->getRequest()->post()) && $entry->update()) {
            $this->success(Yii::t('cms', 'The entry was updated.'));
            return $this->refresh();
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
            return $this->redirect(['index']);
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

    /**
     * @return Sort
     */
    protected function getSort(): Sort
    {
        return new Sort([
            'attributes' => [
                'type' => [
                    'asc' => ['type' => SORT_ASC, 'name' => SORT_ASC],
                    'desc' => ['type' => SORT_DESC, 'name' => SORT_DESC],
                ],
                'name' => [
                    'asc' => ['name' => SORT_ASC],
                    'desc' => ['name' => SORT_DESC],
                ],
                'asset_count' => [
                    'asc' => ['asset_count' => SORT_ASC, 'name' => SORT_ASC],
                    'desc' => ['asset_count' => SORT_DESC, 'name' => SORT_ASC],
                    'default' => SORT_DESC,
                ],
                'section_count' => [
                    'asc' => ['section_count' => SORT_ASC, 'name' => SORT_ASC],
                    'desc' => ['section_count' => SORT_DESC, 'name' => SORT_ASC],
                    'default' => SORT_DESC,
                ],
                'publish_date' => [
                    'asc' => ['publish_date' => SORT_ASC],
                    'desc' => ['publish_date' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
                'updated_at' => [
                    'asc' => ['updated_at' => SORT_ASC],
                    'desc' => ['updated_at' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * @return EntryQuery
     */
    protected function getQuery()
    {
        return EntryForm::find()->replaceI18nAttributes();
    }
}