<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Skeleton\Widgets\Flashes;
use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Models\Actions\ReorderSections;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
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
                            'delete',
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
