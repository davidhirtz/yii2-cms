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
 * Implements {@see PermalinkInterface}. The owner calls {@see static::savePermalinks()} and
 * {@see static::deletePermalinks()} from its own `afterSave()` and `afterDelete()` rather than the trait attaching
 * hooks, because both owners already define those methods.
 *
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

        // Renaming only the slug touches no column on this model, so Yii reports no affected rows even though a
        // permalink was rewritten. Callers read that 0 as "nothing happened".
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
     * Reports a renamed slug as a changed attribute so `TrailBehavior` logs it on the owner's own trail record,
     * rather than on the permalink. The slug is kept out of the UPDATE, so Yii never reports it itself.
     *
     * Call this before {@see static::updateOldSlugAttributes()}, which is what makes the two values equal again.
     *
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

    /**
     * Yii replaces the old attributes with only what it wrote, so the virtual ones have to be restored afterwards or
     * they stay dirty forever and every later save cascades to the whole subtree.
     */
    protected function updateOldSlugAttributes(): void
    {
        foreach ($this->getVirtualSlugAttributes() as $attribute) {
            $this->setOldAttribute($attribute, $this->getAttribute($attribute));
        }
    }

    public function getPermalinks(): PermalinkQuery
    {
        /** @var PermalinkQuery<Permalink> $relation */
        $relation = $this->hasMany(Permalink::class, ['model_id' => 'id'])
            ->andOnCondition([Permalink::tableName() . '.[[model]]' => $this->getPermalinkModelClass()])
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
