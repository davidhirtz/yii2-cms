<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Cms\Models\Actions\DeletePermalinks;
use Hirtz\Cms\Models\Actions\SavePermalinks;
use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * Implements {@see PermalinkInterface}. The owner calls {@see static::savePermalinks()} and
 * {@see static::deletePermalinks()} from its own `afterSave()` and `afterDelete()` rather than the trait attaching
 * hooks, because both owners already define those methods.
 *
 * @mixin ActiveRecord
 */
trait PermalinkTrait
{
    public function getPermalinks(): PermalinkQuery
    {
        /** @var PermalinkQuery<Permalink> $relation */
        $relation = $this->hasMany(Permalink::class, ['model_id' => 'id'])
            ->andOnCondition([Permalink::tableName() . '.[[model]]' => static::class])
            ->indexBy('language');

        return $relation;
    }

    public function getPermalink(?string $language = null): ?Permalink
    {
        /** @var array<string, Permalink> $permalinks */
        $permalinks = $this->permalinks;
        return $permalinks[$language ?? Yii::$app->language] ?? null;
    }

    /**
     * @return list<string>
     */
    public function getPermalinkLanguages(): array
    {
        return array_keys($this->getI18nAttributeNames('slug'));
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

        return Yii::$app->getI18n()->callback(
            $language ?? Yii::$app->language,
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
     * @return list<string> the languages whose URL changed, so the caller can decide whether descendants need
     * rewriting.
     */
    public function savePermalinks(): array
    {
        return SavePermalinks::run(['model' => $this])->getChangedLanguages();
    }

    public function deletePermalinks(): void
    {
        DeletePermalinks::run(['model' => $this]);
    }
}
