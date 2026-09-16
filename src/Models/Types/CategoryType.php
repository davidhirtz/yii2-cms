<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

class CategoryType extends Type
{
    protected bool $allowsDescendants = true;
    protected bool $allowsEntries = true;

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

    public function allowsDescendants(): bool
    {
        return $this->allowsDescendants;
    }

    public function allowsEntries(): bool
    {
        return $this->allowsEntries;
    }
}
