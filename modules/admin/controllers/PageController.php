<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\components\ModuleTrait;
use davidhirtz\yii2\cms\models\Page;
use davidhirtz\yii2\cms\models\queries\PageQuery;
use davidhirtz\yii2\cms\modules\admin\models\forms\PageForm;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\Sort;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class BasePageController.
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 * @see PageController
 *
 * @property \davidhirtz\yii2\cms\modules\admin\Module $module
 */
class PageController extends Controller
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
        $page = $id ? PageForm::findOne($id) : null;

        $query = $this->getQuery()
            ->andFilterWhere(['type' => $type])
            ->orderBy(['position' => SORT_ASC])
            ->matching($q);

        if ($this->getModule()->defaultPageSort) {
            $query->orderBy($this->getModule()->defaultPageSort);
        }

        if ($page) {
            if ($page->sort_by_publish_date) {
                $query->orderBy(['publish_date' => SORT_DESC]);
            }
        }

        $provider = new ActiveDataProvider([
            'query' => $query,
            'sort' => !$query->isSortedByPosition() ? $this->getSort() : false,
        ]);

        if ($query->isSortedByPosition()) {
            $provider->setPagination(false);
        }

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
            'page' => $page,
        ]);
    }

    /**
     * @param int $id
     * @param int $type
     * @return string|\yii\web\Response
     */
    public function actionCreate($id = null, $type = null)
    {
        $page = new PageForm;

        $page->parent_id = $id;
        $page->type = $type;

        if ($page->load(Yii::$app->getRequest()->post()) && $page->insert()) {
            $this->success(Yii::t('cms', 'The page was created and published.'));
            return $this->redirect(['index']);
        }

        /** @noinspection MissedViewInspection */
        return $this->render('create', [
            'page' => $page,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        if (!$page = PageForm::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($page->load(Yii::$app->getRequest()->post()) && $page->update()) {
            $this->success(Yii::t('cms', 'The page was updated.'));
            return $this->refresh();
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'page' => $page,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionDelete($id)
    {
        if (!$page = Page::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($page->delete()) {
            $this->success(Yii::t('cms', 'The page was deleted.'));
            return $this->redirect(['index']);
        }

        $errors = $page->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    /**
     * @param int $id
     */
    public function actionOrder($id = null)
    {
        $pages = Page::find()->select(['id', 'position'])
            ->filterWhere(['parent_id' => $id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        Page::updatePosition($pages, array_flip(Yii::$app->getRequest()->post('page')));
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
                'file_count' => [
                    'asc' => ['file_count' => SORT_ASC, 'name' => SORT_ASC],
                    'desc' => ['file_count' => SORT_DESC, 'name' => SORT_ASC],
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
     * @return PageQuery
     */
    protected function getQuery()
    {
        return PageForm::find()->replaceI18nAttributes();
    }
}