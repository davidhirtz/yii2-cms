<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Actions\SavePermalinks;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Yii;

/**
 * @mixin Entry
 */
trait PermalinkTrait
{
    public function getPermalinks(): PermalinkQuery
    {
        /** @var PermalinkQuery<Permalink> */
        return $this->hasMany(Permalink::class, ['entry_id' => 'id'])
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

    public function composeFormattedSlug(?string $language = null): string
    {
        return (string)$this->getI18nAttribute('slug', $language);
    }

    /**
     * Looked up by exact language: the {@see Permalink::LANGUAGE_ALL} fallback of {@see static::getPermalink()} is
     * for reading only.
     */
    public function buildPermalink(?string $language = null): Permalink
    {
        $language ??= Yii::$app->language;
        $permalink = $this->permalinks[$language] ?? Permalink::create();

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
     * recorded for a URI this model no longer has.
     */
    public function getPermalinkUrl(string $uri, ?string $language = null): false|string
    {
        $route = $this->getRoute();

        if (!$route || !array_key_exists('slug', $route)) {
            return false;
        }

        // A redirect is matched against the request path, so the tenant must not turn this into an absolute URL.
        $route['slug'] = $uri;
        $route['tenant'] = null;

        if ($language === null || $language === Permalink::LANGUAGE_ALL) {
            $language = Yii::$app->sourceLanguage;
        }

        return Yii::$app->getI18n()->callback(
            $language,
            fn (): string => Yii::$app->getUrlManager()->createUrl($route)
        );
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

    public function savePermalinks(): SavePermalinks
    {
        $permalinks = new SavePermalinks($this);
        $permalinks->save();

        return $permalinks;
    }
}
