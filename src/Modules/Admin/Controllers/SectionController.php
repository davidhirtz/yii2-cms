<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Skeleton\Widgets\Flashes;
use Hirtz\Cms\Models\Actions\CreateSectionSet;
use Hirtz\Cms\Models\Actions\DeleteSections;
use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Models\Actions\ReorderSections;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\SectionSetButton;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionGridView;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
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

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'create',
                            'create-set',
                            'delete',
                            'delete-all',
                            'duplicate',
                            'entries',
                            'index',
                            'move',
                            'order',
                            'update',
                            'update-all',
                        ],
                        'roles' => [Entry::AUTH_ENTRY],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create-set' => ['post'],
                    'delete' => ['post'],
                    'delete-all' => ['post'],
                    'duplicate' => ['post'],
                    'order' => ['post'],
                    'move' => ['post'],
                ],
            ],
        ];
    }

    protected function isEntryAllowed(Entry $entry): bool
    {
        return $entry->allowsSections();
    }

    public function actionIndex(int $entry): Response|string
    {
        $entry = $this->findEntry($entry);

        $provider = Yii::$container->get(SectionActiveDataProvider::class, [], [
            'entry' => $entry,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(int $entry): Response|string
    {
        $entry = $this->findEntry($entry);
        $section = Section::create();

        $section->populateEntryRelation($entry);
        $section->loadDefaultValues();

        if (($this->autoCreateSection || ($section->load($this->request->post()) && !$this->request->isFormReload())) && $section->insert()) {
            $this->success(Yii::t('cms', 'SECTION_SUCCESS_CREATED'));
            return $this->redirect(['update', 'id' => $section->id]);
        }

        return $this->render('create', [
            'section' => $section,
        ]);
    }

    /**
     * @see SectionSetButton
     */
    public function actionCreateSet(int $entry): Response
    {
        $entry = $this->findEntry($entry);
        $set = static::getModule()->findSectionSet((int)$this->request->post('set'));

        // A set the entry is not offered is a 404 like an unknown one, rather than a hint that it exists.
        if (!$set?->isAvailable($entry)) {
            throw new NotFoundHttpException();
        }

        $action = CreateSectionSet::create($entry, $set);

        if ($sections = $action->getSections()) {
            $this->success(Yii::t('cms', 'SECTION_SUCCESS_SET_CREATED', [
                'count' => count($sections),
                'name' => $set->getName(),
            ]));
        }

        foreach ($action->getFailed() as $section) {
            $this->error($section);
        }

        return $this->redirect(['index', 'entry' => $entry->id]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $section = $this->findSection($id);

        if ($section->load($this->request->post()) && !$this->request->isFormReload()) {
            if ($section->update()) {
                $this->success(Yii::t('cms', 'SECTION_SUCCESS_UPDATED'));
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
        if ($sectionIds = array_map(intval(...), $this->request->post('selection', []))) {
            $sections = Section::findAll(['id' => $sectionIds]);
            $isUpdated = false;

            foreach ($sections as $section) {
                if ($this->webuser->can(Entry::AUTH_ENTRY)) {
                    if ($section->load($this->request->post())) {
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
                $this->success(Yii::t('cms', 'SECTION_SUCCESS_SELECTED_UPDATED'));
            }
        }

        return $this->redirect([...$this->request->get(), 'index']);
    }


    public function actionMove(int $id, int $entry): Response|string
    {
        $section = $this->findSection($id);
        $entry = $this->findEntry($entry);

        $section->populateEntryRelation($entry);

        if ($section->update()) {
            $this->success(Yii::t('cms', 'SECTION_SUCCESS_MOVED'));
        }

        if ($errors = $section->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect(['update', 'id' => $section->id]);
    }

    public function actionDuplicate(int $id, ?int $entry = null): Response|string
    {
        $section = $this->findSection($id);
        $entry = $entry ? $this->findEntry($entry) : null;

        $duplicate = DuplicateSection::create([
            'section' => $section,
            'entry' => $entry,
        ]);

        if ($errors = $duplicate->getFirstErrors()) {
            $this->error($errors);
            return $this->redirect(['index', 'entry' => $section->entry_id]);
        }

        $this->success(Yii::t('cms', 'SECTION_SUCCESS_DUPLICATED'));
        return $this->redirect(['update', 'id' => $duplicate->id]);
    }

    public function actionDelete(int $id): Response|string
    {
        $section = $this->findSection($id);

        if ($section->delete()) {
            if ($this->request->getIsAjax()) {
                return '';
            }

            $this->success(Yii::t('cms', 'SECTION_SUCCESS_DELETED'));
        }

        if ($errors = $section->getFirstErrors()) {
            $this->error($errors);
        }

        return $this->redirect(['index', 'entry' => $section->entry_id]);
    }

    /**
     * @see SectionGridView::getSelectionButton()
     */
    public function actionDeleteAll(): Response
    {
        $sectionIds = array_map(intval(...), $this->request->post('selection', []));
        $sections = $sectionIds ? Section::findAll(['id' => $sectionIds]) : [];

        if (!$sections) {
            return $this->redirect(['/admin/cms/entry/index']);
        }

        $action = DeleteSections::create($sections);

        if ($count = count($action->getDeleted())) {
            $this->success(Yii::t('cms', 'SECTION_SUCCESS_SELECTED_DELETED', ['count' => $count]));
        }

        foreach ($action->getFailed() as $section) {
            $this->error($section);
        }

        // The grid is scoped to one entry, so the first section names the page the selection was made on.
        return $this->redirect(['index', 'entry' => $sections[0]->entry_id]);
    }

    public function actionOrder(int $entry): string
    {
        $success = ReorderSections::runWithBodyParam('section', [
            'entry' => $this->findEntry($entry),
        ]);

        if ($success) {
            $this->success(Yii::t('cms', 'SECTION_SUCCESS_ORDERED'));
        }

        return (string) Flashes::make();
    }

    public function actionEntries(
        int $id,
        ?int $category = null,
        ?int $parent = null,
        ?int $type = null,
        ?string $q = null
    ): Response|string {
        $section = $this->findSection($id);

        $provider = Yii::$container->get(EntryActiveDataProvider::class, config:[
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
