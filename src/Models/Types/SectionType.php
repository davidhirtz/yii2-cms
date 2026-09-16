<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use Closure;
use Hirtz\Cms\Models\Interfaces\EntryRelationTypeInterface;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\Traits\EntryRelationTypeTrait;
use Hirtz\Cms\Widgets\SectionStack;
use Hirtz\Media\Models\Interfaces\AssetModelTypeInterface;
use Hirtz\Media\Models\Types\Traits\AssetModelTypeTrait;
use Override;

/**
 * The four stages {@see SectionStack} runs over an entry's sections. `visible` is the frontend render filter, not
 * whether the type is offered in the admin — that is {@see static::available()}.
 */
class SectionType extends Type implements AssetModelTypeInterface, EntryRelationTypeInterface
{
    // The class's own `validate()` would shadow the trait's, so it is aliased and called from there instead.
    use AssetModelTypeTrait {
        validate as validateAssetModelType;
    }

    use EntryRelationTypeTrait;

    protected Closure|bool|null $visible = null;
    protected Closure|string|null $group = null;
    protected Closure|string|null $wrapper = null;
    protected ?Closure $collect = null;
    protected Closure|string|null $gridContent = null;

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
     * What the admin grid shows for a section of this type, in place of its name.
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

    #[Override]
    public function validate(string $modelClass): void
    {
        $this->validateAssetModelType($modelClass);
        $this->validateEntriesTypes($modelClass);
    }
}
