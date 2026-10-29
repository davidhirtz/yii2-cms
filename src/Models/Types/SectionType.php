<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use Closure;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Widgets\SectionStack;

/**
 * `visible` is the frontend render filter, not whether the type is offered in the admin
 * ({@see static::available()}).
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
     * The module's `enableBlocks` decides first, and the default is `false` so no section grows a block field from a
     * version bump.
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
     * Under the same value, on by default.
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
     * A type declaring nothing falls through to {@see SectionStack::filter()}.
     *
     * @param Closure(Section): bool|bool $visible
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
     * Rendered as HTML in place of the name, so encode what a user wrote.
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
