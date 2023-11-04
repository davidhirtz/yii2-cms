<?php

namespace davidhirtz\yii2\cms\controllers;

use davidhirtz\yii2\cms\models\builders\EntrySiteRelationsBuilder;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\media\Module;
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
        return $this->actionView(Entry::HOME_SLUG);
    }

    public function actionView(string $entry): Response|string
    {
        $entry = $this->findEntry($entry);
        $this->populateEntryRelations($entry);

        return $this->render('view', [
            'entry' => $entry,
        ]);
    }

    protected function findEntry(string $slug): ?Entry
    {
        $slug = $this->getQuery()
            ->whereSlug($slug)
            ->limit(1)
            ->one();

        if (!$slug?->getRoute()) {
            throw new NotFoundHttpException();
        }

        return $slug;
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