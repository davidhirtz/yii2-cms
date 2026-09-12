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
    /**
     * @var array<string, true> the slug attributes already read off a permalink, keyed by attribute name
     */
    private array $populatedSlugs = [];

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
            ? isset($this->populatedSlugs[$name])
            : parent::isVirtualAttributeLoaded($name);
    }

    #[Override]
    protected function populateVirtualAttributes(string $name): void
    {
        if (!in_array($name, $this->getVirtualSlugAttributes(), true)) {
            parent::populateVirtualAttributes($name);
            return;
        }

        $this->populateSlugAttribute($name);
    }

    #[Override]
    public function afterRefresh(): void
    {
        $this->populatedSlugs = [];
        parent::afterRefresh();
    }

    /**
     * One language at a time: reading the current language's slug must not load the permalink relation for the
     * others, when the record a URI lookup matched already answers it. Skips an attribute that already holds a
     * written value, so a pending change is never clobbered.
     */
    protected function populateSlugAttribute(string $name): void
    {
        $this->populatedSlugs[$name] = true;

        if ($this->getAttribute($name) !== null) {
            return;
        }

        $language = array_search($name, $this->getI18nAttributeNames('slug'), true);
        $slug = $language === false ? null : $this->getPermalink($language)?->slug;

        $this->setAttribute($name, $slug);
        $this->setOldAttribute($name, $slug);
    }
}
