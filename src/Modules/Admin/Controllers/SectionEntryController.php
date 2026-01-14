<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Actions\ReorderSectionEntries;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Hirtz\Skeleton\Helpers\Url;;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SectionEntryController extends AbstractController
{
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
                    'create' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(
        int $section,
        ?int $category = null,
        ?int $parent = null,
        ?string $q = null,
        ?int $type = null
    ): Response|string {
        if (!$type && static::getModule()->defaultEntryType) {
            $this->redirect(Url::current(['type' => static::getModule()->defaultEntryType]));
        }

        $section = $this->findSection($section, Section::AUTH_SECTION_UPDATE);

        $provider = Yii::$container->get(EntryActiveDataProvider::class, [], [
            'section' => $section,
            'innerJoinSection' => false,
            'category' => $category ? Category::findOne($category) : null,
            'parent' => $parent ? Entry::findOne($parent) : null,
            'searchString' => $q,
            'type' => $type,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(int $section, int $entry): Response|string
    {
        $section = $this->findSection($section, Section::AUTH_SECTION_UPDATE);

        $sectionEntry = SectionEntry::create();
        $sectionEntry->populateSectionRelation($section);
        $sectionEntry->entry_id = $entry;

        if (!$sectionEntry->insert()) {
            throw new BadRequestHttpException(current($sectionEntry->getFirstErrors()));
        }

        $this->success(Yii::t('cms', 'Entry added to section.'));
        return $this->redirect($section->getAdminRoute() + ['#' => 'entries']);
    }

    public function actionDelete(int $section, int $entry): Response|string
    {
        $section = $this->findSection($section, Section::AUTH_SECTION_UPDATE);

        $sectionEntry = SectionEntry::findOne([
            'section_id' => $section->id,
            'entry_id' => $entry,
        ]);

        if (!Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['sectionEntry' => $sectionEntry])) {
            throw new ForbiddenHttpException();
        }

        if (!$sectionEntry->delete()) {
            throw new BadRequestHttpException(current($sectionEntry->getFirstErrors()));
        }

        $this->success(Yii::t('cms', 'Entry removed from section.'));
        return $this->redirect($section->getAdminRoute() + ['#' => 'entries']);
    }

    public function actionOrder(int $section): void
    {
        ReorderSectionEntries::runWithBodyParam('entry', [
            'section' => $this->findSection($section, Section::AUTH_SECTION_UPDATE),
        ]);
    }
}
