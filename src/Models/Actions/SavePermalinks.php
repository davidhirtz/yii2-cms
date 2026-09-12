<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Models\Redirect;
use Yii;

class SavePermalinks
{
    /**
     * @var list<string>
     */
    protected array $changedLanguages = [];

    /**
     * @var array<string, string|null> the previous slug per changed slug attribute, for the owner's trail
     */
    protected array $slugChanges = [];

    /**
     * @var array<string, Permalink> the records written, keyed by language
     */
    protected array $savedPermalinks = [];

    /**
     * @var list<string> the languages whose record was deleted
     */
    protected array $deletedLanguages = [];

    /**
     * @param bool $runValidation `false` when the entry validated the records itself through `validateSlug()`,
     * which is every validated save; a save that skipped validation then relies on the unique index
     */
    public function __construct(
        protected Entry $model,
        protected bool $runValidation = true,
    ) {
    }

    public function save(): void
    {
        $this->changedLanguages = [];
        $this->slugChanges = [];
        $this->savedPermalinks = [];
        $this->deletedLanguages = [];

        $languages = $this->model->getPermalinkLanguages();

        foreach ($languages as $language) {
            $this->savePermalink($language);
        }

        $this->deleteStalePermalinks($languages);
    }

    /**
     * @return list<string> the languages whose URL changed
     */
    public function getChangedLanguages(): array
    {
        return $this->changedLanguages;
    }

    /**
     * @return array<string, string|null>
     */
    public function getSlugChanges(): array
    {
        return $this->slugChanges;
    }

    protected function savePermalink(string $language): void
    {
        $slug = (string)$this->model->getI18nAttribute('slug', $language);

        if (!$slug || !$this->model->hasPermalink()) {
            $this->deletePermalink($this->model->getPermalink($language), $language);
            return;
        }

        $permalink = $this->model->buildPermalink($language);

        if (!$permalink->getIsNewRecord() && !$permalink->getDirtyAttributes()) {
            return;
        }

        $previousUri = $permalink->getIsNewRecord() ? null : $permalink->getOldAttribute('uri');
        $previousSlug = $permalink->getIsNewRecord() ? null : $permalink->getOldAttribute('slug');
        $slugChanged = $permalink->getIsNewRecord() || $permalink->isAttributeChanged('slug', false);

        if ($permalink->upsert($this->runValidation)) {
            $this->changedLanguages[] = $language;
            $this->savedPermalinks[$language] = $permalink;

            if ($slugChanged) {
                $this->slugChanges[$this->model->getI18nAttributeName('slug', $language)] = $previousSlug;
            }

            if ($previousUri && $previousUri !== $permalink->uri) {
                $this->insertRedirect($previousUri, $permalink);
            }

            return;
        }

        $model = $this->model::class;
        $this->warn("Permalink for $model {$this->model->id} could not be saved", $permalink);
    }

    /**
     * The request side is host-qualified so the 404 handler matches it on the entry's tenant only; the target is
     * a URL, relative on that host and absolute elsewhere.
     */
    protected function insertRedirect(string $previousUri, Permalink $permalink): void
    {
        $requestUri = Redirect::sanitizeUrl($this->model->getPermalinkRequestUri($previousUri, $permalink->language));
        $previousUrl = Redirect::sanitizeUrl($this->model->getPermalinkUrl($previousUri, $permalink->language));
        $url = Redirect::sanitizeUrl($this->model->getPermalinkUrl($permalink->uri, $permalink->language));

        if (!$requestUri || !$url || $previousUrl === $url) {
            return;
        }

        $this->updatePreviousRedirects($previousUrl, $url);

        $redirect = Redirect::create();
        $redirect->request_uri = $requestUri;
        $redirect->url = $url;

        if (!$redirect->insert()) {
            $this->warn("Redirect from $requestUri could not be saved", $redirect);
        }
    }

    protected function updatePreviousRedirects(string $from, string $to): void
    {
        /** @var Redirect[] $redirects */
        $redirects = Redirect::find()
            ->where(['url' => $from])
            ->all();

        foreach ($redirects as $redirect) {
            $redirect->url = $to;
            $redirect->update();
        }
    }

    /**
     * Removes the records of languages no longer written, e.g. the {@see Permalink::LANGUAGE_ALL} one once the slug
     * became translated, and hands the entry the relation as it is now, without reading it back.
     *
     * @param list<string> $languages
     */
    protected function deleteStalePermalinks(array $languages): void
    {
        $permalinks = $this->model->permalinks;

        foreach ($permalinks as $key => $permalink) {
            if (!in_array($permalink->language, $languages, true)) {
                $this->deletePermalink($permalink, $permalink->language);
                unset($permalinks[$key]);
            }
        }

        foreach ($this->deletedLanguages as $language) {
            unset($permalinks[$language]);
        }

        $this->model->populatePermalinks([...$permalinks, ...$this->savedPermalinks]);
    }

    protected function deletePermalink(?Permalink $permalink, string $language): void
    {
        if ($permalink) {
            $permalink->delete();
            $this->changedLanguages[] = $language;
            $this->deletedLanguages[] = $language;
        }
    }

    protected function warn(string $message, Permalink|Redirect $record): void
    {
        Yii::warning("$message: " . implode(' ', $record->getErrorSummary(true)), __METHOD__);
    }
}
