<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class EntryCategoryController.
 * @package davidhirtz\yii2\cms\modules\admin\controllers
 */
class EntryCategoryController extends Controller
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
                        'actions' => ['create', 'index', 'order', 'delete'],
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
     * @param int $entry
     * @param string $q
     * @return string
     */
    public function actionIndex($entry = null, $q = null)
    {
        if (!$entry = Entry::findOne($entry)) {
            throw new NotFoundHttpException;
        }

        $provider = new CategoryActiveDataProvider([
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
     * @return string|\yii\web\Response
     */
    public function actionCreate($entry, $category)
    {
        if(!($entry=Entry::findOne($entry)) || !($category=Category::findOne($category)))
        {
            throw new NotFoundHttpException();
        }

        $attributes=['product_id'=>$entry->id, 'category_id'=>$category->id];
        $entryCategory=EntryCategory::findOne($attributes) ?: new EntryCategory($attributes);

        if($entryCategory->getIsNewRecord())
        {
            $entryCategory->populateRelation('product', $entry);
            $entryCategory->populateRelation('category', $category);

            $entryCategory->insert();
        }

        return $this->redirect(['index', 'product'=>$entry->id]);

    }


    /**
     * @param int $entry
     * @return string|\yii\web\Response
     */
    public function actionDelete($entry)
    {
        if (!$entry = Entry::findOne($entry)) {
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
}