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
 * @mixin ActiveRecord
 */
trait PermalinkTrait
{
    public function getPermalinks(): PermalinkQuery
    {
        /** @var PermalinkQuery<Permalink> */
        return $this->hasMany(Permalink::class, ['model_id' => 'id'])
            ->andOnCondition([Permalink::tableName() . '.[[model_class]]' => $this->getPermalinkModelClass()])
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
            $permalink->model_class = $this->getPermalinkModelClass();
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
     * Takes the URI rather than reading the current one, so a {@see \Hirtz\Skeleton\Models\Redirect} can be
     * recorded for a URI this model no longer has.
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

    public function savePermalinks(): SavePermalinks
    {
        $permalinks = new SavePermalinks($this);
        $permalinks->save();

        return $permalinks;
    }

    public function deletePermalinks(): void
    {
        (new DeletePermalinks($this))->delete();
    }
}
