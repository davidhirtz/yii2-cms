<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\components\ModuleTrait;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
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
     * @param int $entry
     * @return string|\yii\web\Response
     */
    public function actionIndex($entry)
    {
        $query = EntryForm::find()
            ->where(['id' => $entry])
            ->with([
                'sections' => function (ActiveQuery $query) {
                    $query->replaceI18nAttributes();
                }
            ]);

        if (!$entry = $query->one()) {
            throw new NotFoundHttpException;
        }

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'entry' => $entry,
        ]);
    }

    /**
     * @param int $entry
     * @return string|\yii\web\Response
     */
    public function actionCreate($entry)
    {
        $section = new SectionForm([
            'entry_id' => $entry,
        ]);

        if (!$section->entry) {
            throw new NotFoundHttpException;
        }

        if ($section->load(Yii::$app->getRequest()->post()) && $section->insert()) {
            $this->success(Yii::t('cms', 'The section was created.'));
            return $this->redirect(['index', 'entry' => $section->entry_id]);
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
            return $this->redirect(['index', 'entry' => $section->entry_id]);
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
            return $this->redirect(['index', 'entry' => $section->entry_id]);
        }

        $errors = $section->getFirstErrors();
        throw new ServerErrorHttpException(reset($errors));
    }

    /**
     * @param int $entry
     */
    public function actionOrder($entry)
    {
        $sections = Section::find()->select(['id', 'position'])
            ->where(['entry_id' => $entry])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        Section::updatePosition($sections, array_flip(Yii::$app->getRequest()->post('section')));
    }
}