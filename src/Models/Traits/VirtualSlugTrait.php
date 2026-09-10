<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Override;
use yii\db\ActiveRecord;

/**
 * Backs a model's slug with {@see \Hirtz\Cms\Models\Permalink} records instead of a column. The slug attribute names
 * are still reported by {@see static::attributes()}, so the model, its rules and the admin form treat them as
 * ordinary attributes, but they are kept out of the INSERT/UPDATE and read lazily from the permalink on first
 * access — so an entry that is loaded and never asked for its slug never queries the permalink table.
 *
 * Used together with {@see PermalinkTrait} on a model whose table has no slug column ({@see \Hirtz\Cms\Models\Entry}).
 *
 * @mixin ActiveRecord
 */
trait VirtualSlugTrait
{
    private bool $_slugsPopulated = false;

    #[Override]
    public function attributes(): array
    {
        return [...parent::attributes(), ...$this->getI18nAttributesNames('slug')];
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

    /**
     * @return list<string>
     */
    protected function getVirtualSlugAttributes(): array
    {
        return array_values($this->getI18nAttributesNames('slug'));
    }

    /**
     * Materialises the slug from its permalink the first time it is read, so a load that never touches the slug never
     * loads the permalink relation. A value that was already written is left untouched.
     */
    #[Override]
    public function __get($name)
    {
        if (
            !$this->_slugsPopulated
            && str_starts_with((string)$name, 'slug')
            && !$this->getIsNewRecord()
            && $this->getAttribute($name) === null
            && in_array($name, $this->getVirtualSlugAttributes(), true)
        ) {
            $this->populateSlugAttributes();
            $this->_slugsPopulated = true;
        }

        return parent::__get($name);
    }

    #[Override]
    public function afterRefresh(): void
    {
        $this->_slugsPopulated = false;
        parent::afterRefresh();
    }

    #[Override]
    protected function insertInternal($attributes = null): bool
    {
        return parent::insertInternal($attributes ?? $this->getColumnAttributes());
    }

    #[Override]
    protected function updateInternal($attributes = null): false|int
    {
        // A slug-only change touches no column, so Yii reports 0 affected rows even though a permalink was rewritten.
        $slugs = $this->getDirtyAttributes($this->getVirtualSlugAttributes());
        $result = parent::updateInternal($attributes ?? $this->getColumnAttributes());

        return $result === 0 && $slugs ? 1 : $result;
    }

    /**
     * Reads each unset slug attribute from its permalink, setting the old value too so an unchanged slug is not
     * dirty. Attributes that already hold a written value are skipped, so this never clobbers a pending change.
     */
    protected function populateSlugAttributes(): void
    {
        foreach ($this->getI18nAttributeNames('slug') as $language => $attribute) {
            if ($this->getAttribute($attribute) !== null) {
                continue;
            }

            $slug = $this->getPermalink($language)?->slug;

            $this->setAttribute($attribute, $slug);
            $this->setOldAttribute($attribute, $slug);
        }
    }

    protected function updateOldSlugAttributes(): void
    {
        foreach ($this->getVirtualSlugAttributes() as $attribute) {
            $this->setOldAttribute($attribute, $this->getAttribute($attribute));
        }
    }
}
