<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use Closure;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Widgets\MetaTags;
use Hirtz\Skeleton\Widgets\StructuredData\Thing;

class CategoryType extends Type
{
    protected bool $allowsDescendants = true;
    protected bool $allowsEntries = true;

    /**
     * @var Closure(Category, array<string, mixed>): (array<string, mixed>|Thing|null)|null
     */
    protected ?Closure $structuredData = null;

    /**
     * Whether a category of this type passes its entries down to its children. The module's
     * `inheritNestedCategories` decides first.
     */
    public function allowDescendants(bool $allowDescendants = true): static
    {
        $this->allowsDescendants = $allowDescendants;
        return $this;
    }

    /**
     * Whether entries are put into a category of this type. A category used only to group others declares `false`.
     */
    public function allowEntries(bool $allowEntries = true): static
    {
        $this->allowsEntries = $allowEntries;
        return $this;
    }

    /**
     * The page's structured data, given the node {@see MetaTags} derives for the category. The closure answers
     * that node changed, a {@see Thing} that becomes the page's main entity (an `Event` built from custom attributes),
     * or `null` for none. Anything else it registers itself.
     *
     * @param Closure(Category, array<string, mixed>): (array<string, mixed>|Thing|null)|null $structuredData
     */
    public function structuredData(?Closure $structuredData): static
    {
        $this->structuredData = $structuredData;
        return $this;
    }

    /**
     * @return Closure(Category, array<string, mixed>): (array<string, mixed>|Thing|null)|null
     */
    public function getStructuredData(): ?Closure
    {
        return $this->structuredData;
    }

    public function allowsDescendants(): bool
    {
        return $this->allowsDescendants;
    }

    public function allowsEntries(): bool
    {
        return $this->allowsEntries;
    }
}
