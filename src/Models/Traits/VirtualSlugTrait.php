<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Override;
use yii\db\ActiveRecord;

/**
 * Adds the permalink-backed slug to the virtual attributes of {@see TranslationTrait}, for a model used with
 * {@see PermalinkTrait} that has no slug column. Must be used in a subclass of the class using `TranslationTrait`,
 * as the `parent::` calls below resolve to it.
 *
 * @mixin ActiveRecord
 */
trait VirtualSlugTrait
{
    private bool $_slugsPopulated = false;

    /**
     * @return list<string>
     */
    #[Override]
    public function getTranslationAttributes(): array
    {
        return array_values(array_diff(parent::getTranslationAttributes(), ['slug']));
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function getVirtualAttributes(): array
    {
        return [...parent::getVirtualAttributes(), ...$this->getVirtualSlugAttributes()];
    }

    /**
     * @return list<string>
     */
    protected function getVirtualSlugAttributes(): array
    {
        return array_values($this->getI18nAttributesNames('slug'));
    }

    #[Override]
    protected function isVirtualAttributeLoaded(string $name): bool
    {
        return in_array($name, $this->getVirtualSlugAttributes(), true)
            ? $this->_slugsPopulated
            : parent::isVirtualAttributeLoaded($name);
    }

    #[Override]
    protected function populateVirtualAttributes(string $name): void
    {
        if (!in_array($name, $this->getVirtualSlugAttributes(), true)) {
            parent::populateVirtualAttributes($name);
            return;
        }

        $this->populateSlugAttributes();
        $this->_slugsPopulated = true;
    }

    #[Override]
    public function afterRefresh(): void
    {
        $this->_slugsPopulated = false;
        parent::afterRefresh();
    }

    /**
     * Skips an attribute that already holds a written value, so a pending change is never clobbered.
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
}
