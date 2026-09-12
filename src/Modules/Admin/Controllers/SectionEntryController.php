<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Actions\ReorderSectionEntries;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Widgets\Flashes;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SectionEntryController extends AbstractController
{
    use EntryControllerTrait;
    use SectionControllerTrait;

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
                        'actions' => ['index', 'create', 'delete', 'order'],
                        'roles' => [Section::AUTH_SECTION_UPDATE],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'order' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(int $section): Response|string
    {
        $section = $this->findSection($section, Section::AUTH_SECTION_UPDATE);

        $provider = Yii::$container->get(EntryActiveDataProvider::class, config: [
            'section' => $section,
            'pagination' => false,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(
        int $section,
        ?int $entry = null,
        ?int $category = null,
        ?int $parent = null,
        ?string $q = null,
        ?int $type = null
    ): Response|string {
        $section = $this->findSection($section, Section::AUTH_SECTION_UPDATE);

        if ($this->request->getIsPost()) {
            $entry = $this->findEntry($entry);

            $sectionEntry = SectionEntry::create();
            $sectionEntry->populateSectionRelation($section);
            $sectionEntry->populateEntryRelation($entry);
            $sectionEntry->insert();

            $this->errorOrSuccess($sectionEntry, Yii::t('cms', 'SECTION_ENTRY_SUCCESS_ADDED'));
        }

        if (!$type && static::getModule()->defaultEntryType) {
            $this->redirect(Url::current(['type' => static::getModule()->defaultEntryType]));
        }

        $provider = Yii::$container->get(EntryActiveDataProvider::class, [], [
            'section' => $section,
            'innerJoinSection' => false,
            'category' => Category::findOne($category),
            'parent' => Entry::findOne($parent),
            'searchString' => $q,
            'type' => $type,
        ]);

        return $this->render('create', [
            'provider' => $provider,
        ]);
    }

    public function actionDelete(int $section, int $entry): Response|string
    {
        $section = $this->findSection($section, Section::AUTH_SECTION_UPDATE);

        $sectionEntry = SectionEntry::findOne([
            'section_id' => $section->id,
            'entry_id' => $entry,
        ]);

        if (!$this->webuser->can(Section::AUTH_SECTION_UPDATE, ['section' => $section])) {
            throw new ForbiddenHttpException();
        }

        $sectionEntry->delete();

        $this->errorOrSuccess($sectionEntry, Yii::t('cms', 'SECTION_ENTRY_SUCCESS_REMOVED'));
        return $this->redirect(['index', 'section' => $section->id]);
    }

    public function actionOrder(int $section): string
    {
        $success = ReorderSectionEntries::runWithBodyParam('section-entry', [
            'section' => $this->findSection($section, Section::AUTH_SECTION_UPDATE),
        ]);

        if ($success) {
            $this->success(Yii::t('cms', 'ENTRY_SUCCESS_ORDERED'));
        }

        return (string)Flashes::make();
    }
}
