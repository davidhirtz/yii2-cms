<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\ActiveRecord;
use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Yii;

/**
 * Writes the {@see Permalink} records of a model, one per language, and removes the ones it must no longer have.
 * Returns the languages whose URL changed, so the caller can decide whether descendants need rewriting.
 */
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
    public function savePermalinks(): array
    {
        $this->changedLanguages = [];

        foreach ($this->model->getPermalinkLanguages() as $language) {
            $this->savePermalink($language);
        }

        $this->model->refreshRelation('permalinks');

        return $this->changedLanguages;
    }

    /**
     * @return list<string>
     */
    public function getChangedLanguages(): array
    {
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
        $permalink->uri = $this->model->getFormattedSlug($language);
        $permalink->slug = $slug;

        if (!$permalink->getIsNewRecord() && !$permalink->getDirtyAttributes()) {
            return;
        }

        if ($permalink->upsert()) {
            $this->changedLanguages[] = $language;
            return;
        }

        $model = $this->model::class;
        $errors = implode(' ', $permalink->getErrorSummary(true));

        Yii::warning("Permalink for $model {$this->model->id} could not be saved: $errors", __METHOD__);
    }

    protected function createPermalink(string $language): Permalink
    {
        $permalink = Permalink::create();
        $permalink->language = $language;
        $permalink->model = $this->model::class;
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

    /**
     * @param array{model: ActiveRecord&PermalinkInterface} $params
     */
    public static function run(array $params): static
    {
        $action = Yii::createObject(static::class, $params);
        $action->savePermalinks();

        return $action;
    }
}
