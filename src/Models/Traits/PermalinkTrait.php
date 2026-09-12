<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Actions\SavePermalinks;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Yii;

/**
 * @mixin Entry
 */
trait PermalinkTrait
{
    /**
     * @var array<string, Permalink> the records a query matched by URI, keyed by language, so a request that found
     * the entry through its permalink does not load the relation to read that same record back
     */
    private array $matchedPermalinks = [];

    /**
     * @var array<string, Permalink> the unsaved records per language, shared between validation and save
     */
    private array $newPermalinks = [];

    public function getPermalinks(): PermalinkQuery
    {
        /** @var PermalinkQuery<Permalink> */
        return $this->hasMany(Permalink::class, ['entry_id' => 'id'])
            ->indexBy('language');
    }

    public function getPermalink(?string $language = null): ?Permalink
    {
        $language ??= Yii::$app->language;

        if ($this->matchedPermalinks && !$this->isRelationPopulated('permalinks')) {
            $permalink = $this->matchedPermalinks[$language]
                ?? $this->matchedPermalinks[Permalink::LANGUAGE_ALL]
                ?? null;

            if ($permalink) {
                return $permalink;
            }
        }

        $permalinks = $this->permalinks;

        return $permalinks[$language]
            ?? $permalinks[Permalink::LANGUAGE_ALL]
            ?? null;
    }

    public function populatePermalink(Permalink $permalink): void
    {
        $this->matchedPermalinks[$permalink->language] = $permalink;
    }

    /**
     * @param array<string, Permalink> $permalinks keyed by language
     */
    public function populatePermalinks(array $permalinks): void
    {
        $this->populateRelation('permalinks', $permalinks);
        $this->matchedPermalinks = [];
        $this->newPermalinks = [];
    }

    public function getFormattedSlug(?string $language = null): string
    {
        $permalink = $this->getPermalink($language) ?? current($this->permalinks);
        return $permalink instanceof Permalink ? $permalink->uri : '';
    }

    public function composeFormattedSlug(?string $language = null): string
    {
        return (string)$this->getI18nAttribute('slug', $language);
    }

    /**
     * Looked up by exact language: the {@see Permalink::LANGUAGE_ALL} fallback of {@see static::getPermalink()} is
     * for reading only. An unsaved record is kept per language so validation and save work on the same object.
     */
    public function buildPermalink(?string $language = null): Permalink
    {
        $language ??= Yii::$app->language;
        $permalink = $this->permalinks[$language] ?? ($this->newPermalinks[$language] ??= Permalink::create());

        if ($permalink->getIsNewRecord()) {
            $permalink->language = $language;
        }

        $permalink->uri = $this->composeFormattedSlug($language);
        $permalink->slug = (string)$this->getI18nAttribute('slug', $language);
        $permalink->setAttributes($this->getPermalinkAttributes(), false);

        return $permalink;
    }

    /**
     * @return list<string> the languages a {@see Permalink} record is written for
     */
    public function getPermalinkLanguages(): array
    {
        return $this->isI18nAttribute('slug')
            ? array_keys($this->getI18nAttributeNames('slug'))
            : [Permalink::LANGUAGE_ALL];
    }

    /**
     * Takes the URI rather than reading the current one, so a {@see \Hirtz\Skeleton\Models\Redirect} can be
     * recorded for a URI this model no longer has. Relative on the entry's own host, absolute on another.
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
     * The path a request for this URI carries, qualified by the tenant's host and without a scheme
     * (`www.example.com/de/old`): the form the 404 handler matches a redirect's `request_uri` against.
     */
    public function getPermalinkRequestUri(string $uri, ?string $language = null): false|string
    {
        $url = $this->getPermalinkUrl($uri, $language);

        if ($url === false) {
            return false;
        }

        if (str_contains($url, '://')) {
            return trim(substr($url, strpos($url, '://') + 3), '/');
        }

        $tenant = TenantCollection::getAll()[$this->tenant_id] ?? null;
        $host = $tenant ? parse_url($tenant->getHostInfo(), PHP_URL_HOST) : null;

        return $host ? "$host/" . trim($url, '/') : trim($url, '/');
    }

    /**
     * @return array<string, mixed> the attributes copied onto every {@see Permalink} record of this entry
     */
    public function getPermalinkAttributes(): array
    {
        return [
            'entry_id' => $this->id,
            'tenant_id' => $this->getAttribute('tenant_id'),
        ];
    }

    public function savePermalinks(bool $runValidation = true): SavePermalinks
    {
        $permalinks = new SavePermalinks($this, $runValidation);
        $permalinks->save();

        return $permalinks;
    }
}
