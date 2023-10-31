<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\modules\admin\controllers\traits\SectionTrait;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SectionEntryController extends Controller
{
    use SectionTrait;
    use ModuleTrait;

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'create', 'delete', 'order'],
                        'roles' => ['sectionUpdate'],
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
        ]);
    }

    public function actionIndex(int $section, ?int $category = null, ?int $parent = null, ?string $q = null, ?int $type = null): string
    {
        $section = $this->findSection($section, 'sectionUpdate');

        $provider = Yii::$container->get(EntryActiveDataProvider::class, [], [
            'category' => $category ? Category::findOne($category) : null,
            'innerJoinSection' => false,
            //'parent' => $parent ? Entry::findOne($parent) : null,
            'section' => $section,
            'searchString' => $q,
            'type' => $type,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(int $section, int $entry): Response
    {
        $sectionEntry = new SectionEntry([
            'section_id' => $section,
            'entry_id' => $entry,
        ]);

        if (!Yii::$app->getUser()->can('sectionUpdate', ['sectionEntry' => $sectionEntry])) {
            throw new ForbiddenHttpException();
        }

        if (!$sectionEntry->insert()) {
            throw new BadRequestHttpException(current($sectionEntry->getFirstErrors()));
        }

        if(!Yii::$app->getRequest()->getIsAjax()) {
            $this->success(Yii::t('cms', 'Entry added to section.'));
            return $this->redirect(['index', 'section' => $sectionEntry->section_id]);
        }

        return $this->asJson([]);
    }

    public function actionDelete(int $section, int $entry): Response
    {
        $sectionEntry = SectionEntry::findOne([
            'section_id' => $section,
            'entry_id' => $entry,
        ]);

        if (!Yii::$app->getUser()->can('sectionUpdate', ['sectionEntry' => $sectionEntry])) {
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
        $section = $this->findSection($section, 'sectionUpdate');
        $sectionEntryIds = array_map('intval', array_filter(Yii::$app->getRequest()->post('entry', [])));

        if ($sectionEntryIds) {
            $section->updateSectionEntryOrder($sectionEntryIds);
        }
    }
}