<?php

declare(strict_types=1);

namespace Hirtz\Cms\Controllers;

use Hirtz\Cms\Models\Actions\PreloadEntrySiteRelations;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Module;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\base\ViewNotFoundException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @extends Controller<Module>
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
        if (!$this->module->entryIndexSlug) {
            return '';
        }

        try {
            return $this->actionView($this->module->entryIndexSlug);
        } catch (NotFoundHttpException $exception) {
            if (Entry::find()->whereIndex()->exists()) {
                throw $exception;
            }

            try {
                $this->response->setStatusCode(404);
                return $this->render('missing-index');
            } catch (ViewNotFoundException) {
                throw $exception;
            }
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionView(string $slug): Response|string
    {
        if (str_ends_with($slug, '/')) {
            $slug = rtrim($slug, '/');

            if ($this->redirectTrailingSlash) {
                return $this->redirectWithQueryString(['view', 'slug' => $slug]);
            }
        }

        $entry = $this->findEntry($slug);

        if ($response = $this->validateEntryResponse($entry)) {
            return $response;
        }

        if ($this->action?->id === 'view' && ($response = $this->redirectToCanonicalUrl($entry, $slug))) {
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
            ->whereUri($slug)
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

    /**
     * The lookup ignores letter case, so an entry answers only at its URI as stored and the homepage at `/` alone.
     */
    protected function redirectToCanonicalUrl(Entry $entry, string $slug): ?Response
    {
        if ($entry->isIndex() || trim($slug, '/') !== $entry->getFormattedSlug()) {
            $route = $entry->getRoute();
            return $route ? $this->redirectWithQueryString($route) : null;
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $route
     */
    protected function redirectWithQueryString(array $route): Response
    {
        $url = Url::to($route);

        if ($query = $this->request->getQueryString()) {
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }

        return $this->redirect($url, 301);
    }

    protected function populateEntryRelations(Entry $entry): void
    {
        Yii::$container->get(PreloadEntrySiteRelations::class, config: [
            'entry' => $entry,
        ]);
    }

    /**
     * @return EntryQuery<Entry>
     */
    protected function getQuery(): EntryQuery
    {
        $status = $this->request->getIsDraft() ? Entry::STATUS_DRAFT : Entry::STATUS_ENABLED;

        return Entry::find()
            ->selectSiteAttributes()
            ->withTranslations()
            ->whereStatus($status)
            ->andWhereParentStatus();
    }
}
