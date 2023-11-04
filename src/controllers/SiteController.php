<?php

namespace davidhirtz\yii2\cms\controllers;

use davidhirtz\yii2\cms\models\builders\EntrySiteRelationsBuilder;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @property Module $module
 */
class SiteController extends Controller
{
    public function actionIndex(): Response|string
    {
        return $this->module->entryIndexSlug
            ? $this->actionView($this->module->entryIndexSlug)
            : '';
    }

    public function actionView(string $slug): Response|string
    {
        $entry = $this->findEntry($slug);
        $this->populateEntryRelations($entry);

        return $this->render('view', [
            'entry' => $entry,
        ]);
    }

    protected function findEntry(string $slug): ?Entry
    {
        $entry = $this->getQuery()
            ->whereSlug($slug)
            ->limit(1)
            ->one();

        if (!$entry?->getRoute()) {
            throw new NotFoundHttpException();
        }

        return $entry;
    }

    protected function populateEntryRelations(Entry $entry): void
    {
        Yii::createObject([
            'class' => EntrySiteRelationsBuilder::class,
            'entry' => $entry,
        ]);
    }

    protected function getQuery(): EntryQuery
    {
        $status = Yii::$app->getRequest()->getIsDraft() ? Entry::STATUS_DRAFT : Entry::STATUS_ENABLED;

        return Entry::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->whereStatus($status);
    }
}