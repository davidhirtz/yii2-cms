<?php

declare(strict_types=1);

namespace Hirtz\Cms\Controllers;

use Hirtz\Cms\Models\Builders\EntrySiteRelationsBuilder;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @property Module $module
 */
class SiteController extends Controller
{
    public bool $redirectTrailingSlash = true;

    #[Override]
    public function init(): void
    {
        $this->layout ??= 'main';
        parent::init();
    }

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
        /** @var Entry|null $entry */
        $entry = $this->getQuery()
            ->whereSlug($slug)
            ->limit(1)
            ->one();

        return $entry;
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
        $status = $this->request->getIsDraft() ? Entry::STATUS_DRAFT : Entry::STATUS_ENABLED;

        return Entry::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes()
            ->addSelectI18nSlugTargetAttributes()
            ->whereStatus($status)
            ->andWhereParentStatus();
    }
}
