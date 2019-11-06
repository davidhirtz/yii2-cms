<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;


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
     * @param int $entry
     * @return string|\yii\web\Response
     */
    public function actionIndex($entry)
    {
        $query = Entry::find()
            ->where(['id' => $entry])
            ->with([
                'sections' => function (SectionQuery $query) {
                    $query->with([
                        'assets' => function (AssetQuery $query) {
                            $query->replaceI18nAttributes()
                                ->with(['file', 'file.folder']);
                        }
                    ]);
                },
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
        $section = new Section([
            'entry_id' => $entry,
        ]);

        if (!$section->entry) {
            throw new NotFoundHttpException;
        }

        if ($section->load(Yii::$app->getRequest()->post()) && $section->insert()) {
            $this->success(Yii::t('cms', 'The section was created.'));
            return $this->redirect(['update', 'id' => $section->id]);
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
        $section = $this->findSection($id);

        if ($section->load(Yii::$app->getRequest()->post())) {
            if ($section->update()) {
                $this->success(Yii::t('cms', 'The section was updated.'));
            }

            if (!$section->hasErrors()) {
                return $this->redirect(['index', 'entry' => $section->entry_id]);
            }
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
    public function actionClone($id)
    {
        $section = $this->findSection($id);
        $clone = $section->clone();

        if ($errors = $clone->getFirstErrors()) {
            $this->error($errors);

        } else {
            $this->success(Yii::t('cms', 'The section was duplicated.'));
        }

        return $this->redirect($clone->id ? ['update', 'id' => $clone->id] : ['index', 'entry' => $clone->entry_id]);
    }

    /**
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionDelete($id)
    {
        $section = $this->findSection($id);

        if ($section->delete()) {
            $this->success(Yii::t('cms', 'The section was deleted.'));

        } elseif ($errors = $section->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect(['index', 'entry' => $section->entry_id]);
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

    /**
     * @param int $id
     * @return Section
     * @throws NotFoundHttpException
     */
    private function findSection($id)
    {
        if (!$section = Section::findOne((int)$id)) {
            throw new NotFoundHttpException;
        }

        return $section;
    }
}