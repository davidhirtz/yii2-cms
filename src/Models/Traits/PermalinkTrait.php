<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Actions\DeletePermalinks;
use Hirtz\Cms\Models\Actions\SavePermalinks;
use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Override;
use Yii;
use yii\db\ActiveRecord;

/**
 * @mixin ActiveRecord
 */
trait PermalinkTrait
{
    /**
     * Whether the slug lives in {@see Permalink} records instead of columns on this model. When it does, the slug
     * attribute names are appended to {@see static::attributes()} so the model, its rules and the admin form keep
     * treating them as ordinary attributes, and {@see static::insertInternal()} / {@see static::updateInternal()}
     * keep them out of the INSERT and UPDATE.
     */
    protected function hasVirtualSlug(): bool
    {
        return false;
    }

    #[Override]
    public function attributes(): array
    {
        return $this->hasVirtualSlug()
            ? [...parent::attributes(), ...$this->getI18nAttributesNames('slug')]
            : parent::attributes();
    }

    /**
     * @return list<string>
     */
    protected function getVirtualSlugAttributes(): array
    {
        return $this->hasVirtualSlug() ? array_values($this->getI18nAttributesNames('slug')) : [];
    }

    /**
     * The attributes that are actually columns. Queries select these rather than {@see static::attributes()}, which
     * also reports the virtual slug.
     *
     * @return list<string>
     */
    public function getColumnAttributes(): array
    {
        return array_values(array_diff($this->attributes(), $this->getVirtualSlugAttributes()));
    }

    #[Override]
    protected function insertInternal($attributes = null): bool
    {
        return parent::insertInternal($attributes ?? $this->getColumnAttributes());
    }

    #[Override]
    protected function updateInternal($attributes = null): false|int
    {
        $slugs = $this->getDirtyAttributes($this->getVirtualSlugAttributes());
        $result = parent::updateInternal($attributes ?? $this->getColumnAttributes());

        return $result === 0 && $slugs ? 1 : $result;
    }

    #[Override]
    public function afterFind(): void
    {
        $this->populateSlugAttributes();
        parent::afterFind();
    }

    /**
     * Reads the slug back out of the permalink records. The old values are set too, so an unchanged slug does not
     * count as dirty for the rest of the model's life.
     */
    protected function populateSlugAttributes(): void
    {
        if (!$this->hasVirtualSlug()) {
            return;
        }

        foreach ($this->getI18nAttributeNames('slug') as $language => $attribute) {
            $slug = $this->getPermalink($language)?->slug;

            $this->setAttribute($attribute, $slug);
            $this->setOldAttribute($attribute, $slug);
        }
    }

    /**
     * @param array<string, mixed> $changedAttributes
     * @return array<string, mixed>
     */
    protected function addSlugChangedAttributes(array $changedAttributes): array
    {
        foreach ($this->getVirtualSlugAttributes() as $attribute) {
            $old = $this->getOldAttribute($attribute);

            if ($old !== $this->getAttribute($attribute)) {
                $changedAttributes[$attribute] = $old;
            }
        }

        return $changedAttributes;
    }

    protected function updateOldSlugAttributes(): void
    {
        foreach ($this->getVirtualSlugAttributes() as $attribute) {
            $this->setOldAttribute($attribute, $this->getAttribute($attribute));
        }
    }

    public function getPermalinks(): PermalinkQuery
    {
        /** @var PermalinkQuery<Permalink> */
        return $this->hasMany(Permalink::class, ['model_id' => 'id'])
            ->andOnCondition([Permalink::tableName() . '.[[model]]' => $this->getPermalinkModelClass()])
            ->indexBy('language');
    }

    public function getPermalink(?string $language = null): ?Permalink
    {
        $permalinks = $this->permalinks;

        return $permalinks[$language ?? Yii::$app->language]
            ?? $permalinks[Permalink::LANGUAGE_ALL]
            ?? null;
    }

    public function getFormattedSlug(?string $language = null): string
    {
        $permalink = $this->getPermalink($language) ?? current($this->permalinks);
        return $permalink instanceof Permalink ? $permalink->uri : '';
    }

    /**
     * The URI a save would store for this language. Flat by default — just the slug — because a slug is globally
     * unique; {@see Entry} overrides this to prepend its parent path. Unlike {@see static::getFormattedSlug()}, this
     * recomputes from the current attributes so the save path and validation see pending changes, and an override
     * may query the parent, so keep it off listings.
     */
    public function composeFormattedSlug(?string $language = null): string
    {
        return (string)$this->getI18nAttribute('slug', $language);
    }

    /**
     * The {@see Permalink} this model's current state would be saved as, reused by {@see SavePermalinks} and by
     * validation so both build the record identically.
     */
    public function buildPermalink(?string $language = null): Permalink
    {
        $language ??= Yii::$app->language;
        $permalink = $this->getPermalink($language) ?? Permalink::create();

        if ($permalink->getIsNewRecord()) {
            $permalink->language = $language;
            $permalink->model = $this->getPermalinkModelClass();
            $permalink->model_id = $this->id;
        }

        $permalink->uri = $this->composeFormattedSlug($language);
        $permalink->slug = (string)$this->getI18nAttribute('slug', $language);
        $permalink->setAttributes($this->getPermalinkAttributes(), false);

        return $permalink;
    }

    /**
     * @return list<string>
     */
    public function getPermalinkLanguages(): array
    {
        return $this->isI18nAttribute('slug')
            ? array_keys($this->getI18nAttributeNames('slug'))
            : [Permalink::LANGUAGE_ALL];
    }

    /**
     * The public URL a permalink URI resolves to. Used to record a {@see \Hirtz\Skeleton\Models\Redirect} for a
     * URI this model no longer has, which is why it takes the URI rather than reading the current one.
     */
    public function getPermalinkUrl(string $uri, ?string $language = null): false|string
    {
        $route = $this->getRoute();

        if (!$route || !array_key_exists('slug', $route)) {
            return false;
        }

        $route['slug'] = $uri;

        if ($language === null || $language === Permalink::LANGUAGE_ALL) {
            $language = Yii::$app->sourceLanguage;
        }

        return Yii::$app->getI18n()->callback(
            $language,
            fn (): string => Yii::$app->getUrlManager()->createUrl($route)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getPermalinkAttributes(): array
    {
        return [];
    }

    public function savePermalinks(): array
    {
        return (new SavePermalinks($this))->save();
    }

    public function deletePermalinks(): void
    {
        (new DeletePermalinks($this))->delete();
    }
}
