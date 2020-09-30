<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\CategoryTrait;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\EntryTrait;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Class EntryCategoryController
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class EntryCategoryController extends Controller
{
    use CategoryTrait;
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
                        'actions' => ['index', 'create', 'delete'],
                        'roles' => ['entryCategoryUpdate'],
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
                    'delete' => ['post'],
                    'order' => ['post'],
                    'create' => ['post'],
                ],
            ],
        ]);
    }

    /**
     * @param int|null $entry
     * @param int|null $category
     * @param string|null $q
     * @return string
     */
    public function actionIndex($entry = null, $category = null, $q = null)
    {
        $entry = $this->findEntry($entry, 'entryUpdate');

        /** @var CategoryActiveDataProvider $provider */
        $provider = Yii::createObject([
            'class' => 'davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider',
            'category' => $category ? Category::findOne((int)$category) : null,
            'entry' => $entry,
            'searchString' => $q,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int $entry
     * @param int $category
     * @return string|Response
     */
    public function actionCreate(int $entry, int $category)
    {
        $entryCategory = new EntryCategory([
            'entry_id' => $entry,
            'category_id' => $category,
        ]);

        if (!Yii::$app->getUser()->can('entryCategoryUpdate', ['entryCategory' => $entryCategory])) {
            throw new ForbiddenHttpException();
        }

        $entryCategory->insert();
        return $this->redirect(['index', 'entry' => $entryCategory->entry_id]);
    }

    /**
     * @param int $entry
     * @param int $category
     * @return string|Response
     */
    public function actionDelete(int $entry, int $category)
    {
        $entryCategory = EntryCategory::findOne([
            'entry_id' => $entry,
            'category_id' => $category,
        ]);

        if (!Yii::$app->getUser()->can('entryCategoryUpdate', ['entryCategory' => $entryCategory])) {
            throw new ForbiddenHttpException();
        }

        $entryCategory->delete();
        return $this->redirect(['index', 'entry' => $entryCategory->entry_id]);
    }

    /**
     * @param int $category
     */
    public function actionOrder(int $category)
    {
        $category = $this->findCategory($category, 'entryOrder');

        $entries = EntryCategory::find()
            ->where(['category_id' => $category->id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        EntryCategory::updatePosition($entries, array_flip(Yii::$app->getRequest()->post('entry')));
    }
}