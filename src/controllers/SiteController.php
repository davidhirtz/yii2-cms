<?php

declare(strict_types=1);

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
    /**
     * @var bool Whether to redirect trailing slashes from URLs
     */
    public bool $redirectTrailingSlash = true;

    public function actionIndex(): Response|string
    {
        return $this->module->entryIndexSlug
            ? $this->actionView($this->module->entryIndexSlug)
            : '';
    }

    public function actionView(string $slug): Response|string
    {
        if (str_ends_with($slug, '/')) {
            $slug = rtrim($slug, '/');

            if ($this->redirectTrailingSlash) {
                return $this->redirect(['view', 'slug' => $slug], 301);
            }
        }

        $entry = $this->findEntry($slug);

        if ($response = $this->validateEntryResponse($entry)) {
            return $response;
        }

        $this->populateEntryRelations($entry);

        return $this->render($entry->getViewFile() ?? 'view', [
            'entry' => $entry,
        ]);
    }

    protected function findEntry(string $slug): ?Entry
    {
        return $this->getQuery()
            ->whereSlug($slug)
            ->limit(1)
            ->one();
    }

    protected function validateEntryResponse(?Entry $entry): ?Response
    {
        if (!$entry?->getRoute()) {
            throw new NotFoundHttpException();
        }

        return null;
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
            ->addSelectI18nSlugTargetAttributes()
            ->whereStatus($status)
            ->andWhereParentStatus();
    }
}
