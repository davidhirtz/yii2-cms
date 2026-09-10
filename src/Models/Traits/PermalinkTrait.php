<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Actions\DeletePermalinks;
use Hirtz\Cms\Models\Actions\SavePermalinks;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * Shared {@see Permalink} behaviour for a model reachable under its own URL. A model whose slug lives in permalink
 * records rather than a column additionally uses {@see VirtualSlugTrait}.
 *
 * @mixin ActiveRecord
 */
trait PermalinkTrait
{
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
     * unique; {@see \Hirtz\Cms\Models\Entry} overrides this to prepend its parent path. Unlike
     * {@see static::getFormattedSlug()}, this recomputes from the current attributes so the save path and validation
     * see pending changes, and an override may query the parent, so keep it off listings.
     */
    public function composeFormattedSlug(?string $language = null): string
    {
        return (string)$this->getI18nAttribute('slug', $language);
    }

    /**
     * The {@see Permalink} this model's current state would be saved as, reused by {@see SavePermalinks} and by
     * validation so both build the record identically.
     *
     * The record is looked up by its exact language: the {@see Permalink::LANGUAGE_ALL} fallback of
     * {@see static::getPermalink()} is for reading, and reusing that record here would rewrite the language-agnostic
     * URL with one language's slug once the slug becomes translated.
     */
    public function buildPermalink(?string $language = null): Permalink
    {
        $language ??= Yii::$app->language;
        $permalink = $this->permalinks[$language] ?? Permalink::create();

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

    /**
     * @return list<string> the languages whose URL changed
     */
    public function savePermalinks(): array
    {
        return (new SavePermalinks($this))->save();
    }

    public function deletePermalinks(): void
    {
        (new DeletePermalinks($this))->delete();
    }
}
