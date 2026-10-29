<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Override;
use yii\db\ActiveRecord;

/**
 * Must be used in a subclass of the class using {@see TranslationTrait}, as the `parent::` calls resolve to it.
 *
 * @mixin ActiveRecord
 */
trait VirtualSlugTrait
{
    /**
     * @var array<string, true>
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
        return $this->getI18nAttributesNames('slug');
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
     * One language at a time, so the others' permalinks are not loaded, and never over a pending written value.
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
