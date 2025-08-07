<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\actions\ReorderSectionEntries;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\SectionTrait;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SectionEntryController extends AbstractController
{
    use SectionTrait;

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
        $sectionEntry = SectionEntry::create();
        $sectionEntry->section_id = $section;
        $sectionEntry->entry_id = $entry;

        if (!Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['sectionEntry' => $sectionEntry])) {
            throw new ForbiddenHttpException();
        }

        if (!$sectionEntry->insert()) {
            throw new BadRequestHttpException(current($sectionEntry->getFirstErrors()));
        }

        if (!Yii::$app->getRequest()->getIsAjax()) {
            $this->success(Yii::t('cms', 'Entry added to section.'));
            return $this->redirect(['index', 'section' => $sectionEntry->section_id]);
        }

        return $this->asJson([]);
    }

    public function actionDelete(int $section, int $entry): Response|string
    {
        $sectionEntry = SectionEntry::findOne([
            'section_id' => $section,
            'entry_id' => $entry,
        ]);

        if (!Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['sectionEntry' => $sectionEntry])) {
            throw new ForbiddenHttpException();
        }

        if (!$sectionEntry->delete()) {
            throw new BadRequestHttpException(current($sectionEntry->getFirstErrors()));
        }

        if (!Yii::$app->getRequest()->getIsAjax()) {
            $this->success(Yii::t('cms', 'Entry removed from section.'));
            return $this->redirect(['index', 'section' => $sectionEntry->section_id]);
        }

        return $this->asJson([]);
    }

    public function actionOrder(int $section): void
    {
        ReorderSectionEntries::runWithBodyParam('entry', [
            'section' => $this->findSection($section, Section::AUTH_SECTION_UPDATE),
        ]);
    }
}
