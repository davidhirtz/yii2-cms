<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\components\ModuleTrait;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\models\forms\PageForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;


/**
 * Class SectionController.
 * @package app\modules\content\modules\admin\controllers
 */
class SectionController extends Controller
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
                        'actions' => ['create', 'delete', 'index', 'order', 'update'],
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
     * @param int $page
     * @return string|\yii\web\Response
     */
    public function actionIndex($page)
    {
        $query = PageForm::find()
            ->where(['id' => $page])
            ->with([
                'sections' => function (ActiveQuery $query) {
                    $query->replaceI18nAttributes();
                }
            ]);

        if (!$page = $query->one()) {
            throw new NotFoundHttpException;
        }

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'page' => $page,
        ]);
    }

    /**
     * @param int $page
     * @return string|\yii\web\Response
     */
    public function actionCreate($page)
    {
        $section = new SectionForm([
            'page_id' => $page,
        ]);

        if (!$section->page) {
            throw new NotFoundHttpException;
        }

        if ($section->load(Yii::$app->getRequest()->post()) && $section->insert()) {
            $this->success(Yii::t('cms', 'The section was created.'));
            return $this->redirect(['index', 'page' => $section->id]);
        }

        /** @noinspection MissedViewInspection */
        return $this->render('create', [
            'section' => $section,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        if (!$section = SectionForm::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($section->load(Yii::$app->getRequest()->post()) && $section->update()) {
            $this->success(Yii::t('cms', 'The section was updated.'));
            return $this->redirect(['index', 'page' => $section->page_id]);
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'section' => $section,
        ]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionDelete($id)
    {
        if (!$section = Section::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if ($section->delete()) {
            $this->success(Yii::t('cms', 'The section was deleted.'));
            return $this->redirect(['index', 'page' => $section->page_id]);
        }

        $errors = $section->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    /**
     * @param int $page
     */
    public function actionOrder($page)
    {
        $sections = Section::find()->select(['id', 'position'])
            ->where(['page_id' => $page])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        Section::updatePosition($sections, array_flip(Yii::$app->getRequest()->post('section')));
    }
}