<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\ActiveRecord;
use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Models\Redirect;
use Yii;

class SavePermalinks
{
    /**
     * @var list<string>
     */
    protected array $changedLanguages = [];

    public function __construct(
        protected ActiveRecord&PermalinkInterface $model,
    ) {
    }

    /**
     * @return list<string>
     */
    public function save(): array
    {
        $this->changedLanguages = [];

        foreach ($this->model->getPermalinkLanguages() as $language) {
            $this->savePermalink($language);
        }

        $this->model->refreshRelation('permalinks');

        return $this->changedLanguages;
    }

    protected function savePermalink(string $language): void
    {
        $permalink = $this->model->getPermalink($language);
        $slug = (string)$this->model->getI18nAttribute('slug', $language);

        if (!$slug || !$this->model->hasPermalink()) {
            $this->deletePermalink($permalink, $language);
            return;
        }

        $permalink ??= $this->createPermalink($language);
        $permalink->uri = $this->model->composeFormattedSlug($language);
        $permalink->slug = $slug;
        $permalink->setAttributes($this->model->getPermalinkAttributes(), false);

        if (!$permalink->getIsNewRecord() && !$permalink->getDirtyAttributes()) {
            return;
        }

        $previousUri = $permalink->getIsNewRecord() ? null : $permalink->getOldAttribute('uri');

        if ($permalink->upsert()) {
            $this->changedLanguages[] = $language;

            if ($previousUri && $previousUri !== $permalink->uri) {
                $this->insertRedirect($previousUri, $permalink);
            }

            return;
        }

        $model = $this->model::class;
        $errors = implode(' ', $permalink->getErrorSummary(true));

        Yii::warning("Permalink for $model {$this->model->id} could not be saved: $errors", __METHOD__);
    }

    protected function insertRedirect(string $previousUri, Permalink $permalink): void
    {
        $from = Redirect::sanitizeUrl($this->model->getPermalinkUrl($previousUri, $permalink->language));
        $to = Redirect::sanitizeUrl($this->model->getPermalinkUrl($permalink->uri, $permalink->language));

        if (!$from || !$to || $from === $to) {
            return;
        }

        $this->updatePreviousRedirects($from, $to);

        $redirect = Redirect::create();
        $redirect->request_uri = $from;
        $redirect->url = $to;
        $redirect->insert();
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

    protected function createPermalink(string $language): Permalink
    {
        $permalink = Permalink::create();
        $permalink->language = $language;
        $permalink->model = $this->model->getPermalinkModelClass();
        $permalink->model_id = $this->model->id;

        return $permalink;
    }

    protected function deletePermalink(?Permalink $permalink, string $language): void
    {
        if ($permalink) {
            $permalink->delete();
            $this->changedLanguages[] = $language;
        }
    }
}
