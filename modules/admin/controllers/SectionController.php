<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\EntryTrait;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\SectionTrait;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;


/**
 * Admin CRUD actions for {@see Section}.
 */
class SectionController extends Controller
{
    use EntryTrait;
    use SectionTrait;
    use ModuleTrait;

    /**
     * @var bool whether sections should be saved directly on create
     */
    public $autoCreateSection = true;

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
                        'actions' => ['entries', 'index', 'update', 'update-all'],
                        'roles' => ['sectionUpdate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['clone', 'create'],
                        'roles' => ['sectionCreate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['sectionDelete'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => ['sectionOrder'],
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
     * @return string|Response
     */
    public function actionIndex(int $entry)
    {
        $query = Entry::find()
            ->where(['id' => $entry])
            ->with([
                'sections' => function (SectionQuery $query) {
                    $query->with([
                        'assets' => function (AssetQuery $query) {
                            $query->with(['file', 'file.folder']);
                        }
                    ]);
                },
            ]);

        if (!$entry = $query->one()) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->getUser()->can('entryUpdate', ['entry' => $entry])) {
            throw new ForbiddenHttpException();
        }

        return $this->render('index', [
            'entry' => $entry,
        ]);
    }

    public function actionCreate(?int $entry = null): Response|string
    {
        $section = Section::create();
        $section->loadDefaultValues();
        $section->entry_id = $entry;

        if (!$section->entry) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->getUser()->can('sectionCreate', ['section' => $section])) {
            throw new ForbiddenHttpException();
        }

        if (($this->autoCreateSection || $section->load(Yii::$app->getRequest()->post())) && $section->insert()) {
            $this->success(Yii::t('cms', 'The section was created.'));
            return $this->redirect(['update', 'id' => $section->id]);
        }

        return $this->render('create', [
            'section' => $section,
        ]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate(int $id)
    {
        $section = $this->findSection($id, 'sectionUpdate');

        if ($section->load(Yii::$app->getRequest()->post())) {
            if ($section->update()) {
                $this->success(Yii::t('cms', 'The section was updated.'));
            }

            if (!$section->hasErrors()) {
                return $this->refresh();
            }
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'section' => $section,
        ]);
    }

    /**
     * @return Response
     */
    public function actionUpdateAll()
    {
        $request = Yii::$app->getRequest();

        if ($entryIds = array_map('intval', $request->post('selection', []))) {
            $sections = Section::findAll(['id' => $entryIds]);
            $isUpdated = false;

            foreach ($sections as $section) {
                if (Yii::$app->getUser()->can('sectionUpdate', ['section' => $section])) {
                    if ($section->load($request->post())) {
                        if ($section->update()) {
                            $isUpdated = true;
                        }

                        if ($section->hasErrors()) {
                            $this->error($section->getFirstErrors());
                        }
                    }
                }
            }

            if ($isUpdated) {
                $this->success(Yii::t('cms', 'The selected sections were updated.'));
            }
        }

        return $this->redirect(array_merge($request->get(), ['index']));
    }

    /**
     * Clones or copies section. Additional changes can be set via POST (e.g. the entry id for
     * copying the section to another entry).
     *
     * @param int $id
     * @return Response
     */
    public function actionClone(int $id)
    {
        $section = $this->findSection($id, 'sectionUpdate');
        $entryId = $section->entry_id;

        $section->load(Yii::$app->getRequest()->post());
        $clone = $section->clone();

        if ($errors = $clone->getFirstErrors()) {
            $this->error($errors);
            return $this->redirect(['index', 'entry' => $entryId]);
        }

        $this->success(Yii::t('cms', 'The section was duplicated.'));
        return $this->redirect(['update', 'id' => $clone->id]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionDelete(int $id)
    {
        $section = $this->findSection($id, 'sectionDelete');

        if ($section->delete()) {
            if (Yii::$app->getRequest()->getIsAjax()) {
                return '';
            }

            $this->success(Yii::t('cms', 'The section was deleted.'));
        }

        if ($errors = $section->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect(['index', 'entry' => $section->entry_id]);
    }

    /**
     * @param int $entry
     */
    public function actionOrder($entry)
    {
        $entry = $this->findEntry($entry, 'sectionOrder');
        $sectionIds = array_map('intval', array_filter(Yii::$app->getRequest()->post('section', [])));

        if ($sectionIds) {
            $entry->updateSectionOrder($sectionIds);
        }
    }

    /**
     * Displays a list of entries for copying / moving section.
     *
     * @param int $id
     * @param int|null $category
     * @param int|null $type
     * @param string|null $q
     * @return string
     */
    public function actionEntries(int $id, $category = null, $type = null, $q = null)
    {
        $section = $this->findSection($id, 'sectionUpdate');

        /** @var EntryActiveDataProvider $provider */
        $provider = Yii::createObject([
            'class' => 'davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider',
            'category' => $category ? Category::findOne((int)$category) : null,
            'searchString' => $q,
            'type' => $type,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('entries', [
            'section' => $section,
            'provider' => $provider,
        ]);
    }
}