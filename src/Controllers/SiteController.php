<?php

declare(strict_types=1);

namespace Hirtz\Cms\Controllers;

use Hirtz\Cms\Models\Builders\EntrySiteRelationsBuilder;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Queries\CategoryQuery;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
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

        $permalink = $this->findPermalink($slug);

        if (!$permalink) {
            throw new NotFoundHttpException();
        }

        return $this->renderPermalink($permalink);
    }

    protected function findPermalink(string $slug): ?Permalink
    {
        return Permalink::find()
            ->whereUri($slug)
            ->limit(1)
            ->one();
    }

    /**
     * Override this method to render models beyond entries and categories. {@see Permalink::isModel()} matches
     * subclasses, which matters because the record stores the class the container resolved, not the base one.
     */
    protected function renderPermalink(Permalink $permalink): Response|string
    {
        if ($permalink->isModel(Entry::class)) {
            return $this->renderEntry($permalink);
        }

        if ($permalink->isModel(Category::class)) {
            return $this->renderCategory($permalink);
        }

        throw new NotFoundHttpException();
    }

    protected function renderEntry(Permalink $permalink): Response|string
    {
        $entry = $this->findEntry($permalink);

        if ($response = $this->validateEntryResponse($entry)) {
            return $response;
        }

        $this->populateEntryRelations($entry);

        return $this->render($entry->getViewFile() ?? 'view', [
            'entry' => $entry,
        ]);
    }

    protected function findEntry(Permalink $permalink): ?Entry
    {
        /** @var Entry|null $entry */
        $entry = $this->getQuery()
            ->whereId($permalink->model_id)
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
        Yii::$container->get(EntrySiteRelationsBuilder::class, config: [
            'entry' => $entry,
        ]);
    }

    protected function renderCategory(Permalink $permalink): Response|string
    {
        $category = $this->findCategory($permalink);

        if ($response = $this->validateCategoryResponse($category)) {
            return $response;
        }

        return $this->render('category', [
            'category' => $category,
            'entries' => $this->findCategoryEntries($category),
        ]);
    }

    /**
     * Guards against a permalink that outlived its category's URL, the way {@see static::validateEntryResponse()}
     * guards the entry branch. Turning `Module::$enableCategoryUrls` off leaves the records behind until they are
     * rebuilt, and they must not keep resolving in the meantime.
     */
    protected function validateCategoryResponse(?Category $category): ?Response
    {
        if (!$category?->hasPermalink()) {
            throw new NotFoundHttpException();
        }

        return null;
    }

    /**
     * @return Entry[]
     */
    protected function findCategoryEntries(Category $category): array
    {
        return $this->getQuery()
            ->whereCategory($category)
            ->all();
    }

    protected function findCategory(Permalink $permalink): ?Category
    {
        /** @var Category|null $category */
        $category = $this->getCategoryQuery()
            ->andWhere([Category::tableName() . '.[[id]]' => $permalink->model_id])
            ->limit(1)
            ->one();

        return $category;
    }

    protected function getQuery(): EntryQuery
    {
        $status = $this->request->getIsDraft() ? Entry::STATUS_DRAFT : Entry::STATUS_ENABLED;

        return Entry::find()
            ->selectSiteAttributes()
            ->withTranslations()
            ->withPermalinks()
            ->whereStatus($status)
            ->andWhereParentStatus();
    }

    protected function getCategoryQuery(): CategoryQuery
    {
        $status = $this->request->getIsDraft() ? Category::STATUS_DRAFT : Category::STATUS_ENABLED;

        return Category::find()
            ->selectSiteAttributes()
            ->withTranslations()
            ->whereStatus($status);
    }
}
