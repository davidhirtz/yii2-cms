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

    /**
     * @var array<string, string|null> the previous slug per changed slug attribute, for the owner's trail
     */
    protected array $slugChanges = [];

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
        $this->slugChanges = [];

        foreach ($this->model->getPermalinkLanguages() as $language) {
            $this->savePermalink($language);
        }

        $this->model->refreshRelation('permalinks');

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

        if ($permalink->upsert()) {
            $this->changedLanguages[] = $language;

            if ($slugChanged) {
                $this->slugChanges[$this->model->getI18nAttributeName('slug', $language)] = $previousSlug;
            }

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

    protected function deletePermalink(?Permalink $permalink, string $language): void
    {
        if ($permalink) {
            $permalink->delete();
            $this->changedLanguages[] = $language;
        }
    }
}
