<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use Closure;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Widgets\SectionStack;

/**
 * The four stages {@see SectionStack} runs over an entry's sections. `visible` is the frontend render filter, not
 * whether the type is offered in the admin — that is {@see static::available()}. A block type, so that one shared
 * with blocks is the same definition on both.
 */
class SectionType extends BlockType
{
    protected Closure|bool|null $visible = null;
    protected Closure|string|null $group = null;
    protected Closure|string|null $wrapper = null;
    protected ?Closure $collect = null;
    protected Closure|string|null $gridContent = null;
    protected bool $allowsBlock = false;
    protected bool $isSharedWithBlocks = true;

    /**
     * Whether a section of this type carries a block instead of content of its own. The module's `enableBlocks`
     * decides first, and the default is `false` on both sides: a section that has always been its own content
     * must not grow a block field from a version bump.
     */
    public function allowBlock(bool $allowBlock = true): static
    {
        $this->allowsBlock = $allowBlock;
        return $this;
    }

    public function allowsBlock(): bool
    {
        return $this->allowsBlock;
    }

    /**
     * Offers the type for blocks too, under the same value, and lets a section of it become one. On by default.
     *
     * @see Block::getTypesWithSharedSectionTypes()
     */
    public function shareWithBlocks(bool $shareWithBlocks = true): static
    {
        $this->isSharedWithBlocks = $shareWithBlocks;
        return $this;
    }

    /**
     * A type carrying a block or naming a section class of its own is never shared, whatever it declares.
     */
    public function isSharedWithBlocks(): bool
    {
        return $this->isSharedWithBlocks && !$this->allowsBlock && $this->modelClass === null;
    }

    /**
     * @param Closure(Section): bool|bool $visible whether the section is rendered on the site; a type declaring
     * nothing falls through to the stack's own {@see SectionStack::filter()}
     */
    public function visible(Closure|bool $visible = true): static
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @param Closure(Section): ?string|string|null $group
     */
    public function group(Closure|string|null $group): static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @param Closure(Section): ?string|string|null $wrapper
     */
    public function wrapper(Closure|string|null $wrapper): static
    {
        $this->wrapper = $wrapper;
        return $this;
    }

    /**
     * @param Closure(Section, list<Section>): list<Section>|null $collect
     */
    public function collect(?Closure $collect): static
    {
        $this->collect = $collect;
        return $this;
    }

    /**
     * What the admin grid shows for a section of this type, in place of its name, as HTML: encode what a user wrote.
     *
     * @param Closure(Section): ?string|string|null $gridContent
     */
    public function gridContent(Closure|string|null $gridContent): static
    {
        $this->gridContent = $gridContent;
        return $this;
    }

    public function getVisible(): Closure|bool|null
    {
        return $this->visible;
    }

    public function getGroup(): Closure|string|null
    {
        return $this->group;
    }

    public function getWrapper(): Closure|string|null
    {
        return $this->wrapper;
    }

    public function getCollect(): ?Closure
    {
        return $this->collect;
    }

    public function getGridContent(): Closure|string|null
    {
        return $this->gridContent;
    }
}
