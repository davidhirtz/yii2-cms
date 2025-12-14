<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Models\Actions\ReorderSections;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\AssetQuery;
use Hirtz\Cms\Models\Queries\SectionQuery;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SectionController extends AbstractController
{
    use EntryControllerTrait;
    use SectionControllerTrait;

    /**
     * @var bool whether sections should be automatically inserted in {@see static::actionCreate()}.
     */
    public bool $autoCreateSection = true;

    #[\Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['entries', 'index', 'update', 'update-all'],
                        'roles' => [Section::AUTH_SECTION_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'duplicate', 'move'],
                        'roles' => [Section::AUTH_SECTION_CREATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [Section::AUTH_SECTION_DELETE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => [Section::AUTH_SECTION_ORDER],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'duplicate' => ['post'],
                    'order' => ['post'],
                    'move' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(int $entry): Response|string
    {
        $query = Entry::find()
            ->where(['id' => $entry])
            ->with([
                'sections' => function (SectionQuery $query): void {
                    $query->with([
                        'assets' => function (AssetQuery $query): void {
                            $query->with(['file', 'file.folder']);
                        }
                    ]);
                },
            ]);

        if (!$entry = $query->one()) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $entry])) {
            throw new ForbiddenHttpException();
        }

        return $this->render('index', [
            'entry' => $entry,
        ]);
    }

    public function actionCreate(int $entry): Response|string
    {
        $entry = $this->findEntry($entry, Section::AUTH_SECTION_CREATE);
        $section = Section::create();

        $section->populateEntryRelation($entry);
        $section->loadDefaultValues();

        if (($this->autoCreateSection || $section->load(Yii::$app->getRequest()->post())) && $section->insert()) {
            $this->success(Yii::t('cms', 'The section was created.'));
            return $this->redirect(['update', 'id' => $section->id]);
        }

        return $this->render('create', [
            'section' => $section,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $section = $this->findSection($id, Section::AUTH_SECTION_UPDATE);

        if ($section->load(Yii::$app->getRequest()->post())) {
            if ($section->update()) {
                $this->success(Yii::t('cms', 'The section was updated.'));
            }

            if (!$section->hasErrors()) {
                return $this->refresh();
            }
        }

        return $this->render('update', [
            'section' => $section,
        ]);
    }

    public function actionUpdateAll(): Response|string
    {
        $request = Yii::$app->getRequest();

        if ($sectionIds = array_map(intval(...), $request->post('selection', []))) {
            $sections = Section::findAll(['id' => $sectionIds]);
            $isUpdated = false;

            foreach ($sections as $section) {
                if (Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['section' => $section])) {
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

        return $this->redirect([...$request->get(), 'index']);
    }


    public function actionMove(int $id, int $entry): Response|string
    {
        $section = $this->findSection($id, Section::AUTH_SECTION_UPDATE);
        $entry = $this->findEntry($entry, Section::AUTH_SECTION_UPDATE);

        $section->populateEntryRelation($entry);

        if ($section->update()) {
            $this->success(Yii::t('cms', 'The section was moved.'));
        }

        if ($errors = $section->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect(['update', 'id' => $section->id]);
    }

    public function actionDuplicate(int $id, ?int $entry = null): Response|string
    {
        $section = $this->findSection($id, Section::AUTH_SECTION_UPDATE);
        $entry = $entry ? $this->findEntry($entry, Section::AUTH_SECTION_UPDATE) : null;

        $duplicate = DuplicateSection::create([
            'section' => $section,
            'entry' => $entry,
        ]);

        if ($errors = $duplicate->getFirstErrors()) {
            $this->error($errors);
            return $this->redirect(['index', 'entry' => $section->entry_id]);
        }

        $this->success(Yii::t('cms', 'The section was duplicated.'));
        return $this->redirect(['update', 'id' => $duplicate->id]);
    }

    public function actionDelete(int $id): Response|string
    {
        $section = $this->findSection($id, Section::AUTH_SECTION_DELETE);

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

    public function actionOrder(int $entry): void
    {
        ReorderSections::runWithBodyParam('section', [
            'entry' => $this->findEntry($entry, Section::AUTH_SECTION_ORDER),
        ]);
    }

    public function actionEntries(
        int $id,
        ?int $category = null,
        ?int $parent = null,
        ?int $type = null,
        ?string $q = null
    ): Response|string {
        $section = $this->findSection($id, Section::AUTH_SECTION_UPDATE);

        $provider = Yii::$container->get(EntryActiveDataProvider::class, [], [
            'category' => Category::findOne($category),
            'parent' => $parent ? Entry::findOne($parent) : null,
            'searchString' => $q,
            'type' => $type,
        ]);

        return $this->render('entries', [
            'section' => $section,
            'provider' => $provider,
        ]);
    }
}
