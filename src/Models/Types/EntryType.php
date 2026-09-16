<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use Hirtz\Media\Models\Interfaces\AssetModelTypeInterface;
use Hirtz\Media\Models\Types\Traits\AssetModelTypeTrait;

class EntryType extends Type implements AssetModelTypeInterface
{
    use AssetModelTypeTrait;

    /**
     * @var array<string, int>|null
     */
    protected ?array $orderBy = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $sort = null;
    protected ?bool $showCategories = null;
    protected ?bool $showCategoryDropdown = null;
    protected bool $allowsCategories = true;
    protected bool $allowsSections = true;
    protected bool $allowsDescendants = true;

    /**
     * Whether an entry of this type is put into categories. The module's `enableCategories` decides first: a type
     * only ever narrows what the installation turned on, which is why these take a plain `bool` where
     * {@see static::showCategories()} — a grid setting with a default of its own to fall through to — takes `?bool`.
     */
    public function allowCategories(bool $allowCategories = true): static
    {
        $this->allowsCategories = $allowCategories;
        return $this;
    }

    public function allowSections(bool $allowSections = true): static
    {
        $this->allowsSections = $allowSections;
        return $this;
    }

    public function allowDescendants(bool $allowDescendants = true): static
    {
        $this->allowsDescendants = $allowDescendants;
        return $this;
    }

    /**
     * @param array<string, int>|null $orderBy the entry index order, which also disables manual ordering
     */
    public function orderBy(?array $orderBy): static
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * @param array<string, mixed>|null $sort
     */
    public function sort(?array $sort): static
    {
        $this->sort = $sort;
        return $this;
    }

    public function showCategories(?bool $showCategories = true): static
    {
        $this->showCategories = $showCategories;
        return $this;
    }

    public function showCategoryDropdown(?bool $showCategoryDropdown = true): static
    {
        $this->showCategoryDropdown = $showCategoryDropdown;
        return $this;
    }

    /**
     * @return array<string, int>|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSort(): ?array
    {
        return $this->sort;
    }

    public function allowsCategories(): bool
    {
        return $this->allowsCategories;
    }

    public function allowsSections(): bool
    {
        return $this->allowsSections;
    }

    public function allowsDescendants(): bool
    {
        return $this->allowsDescendants;
    }

    public function showsCategories(): ?bool
    {
        return $this->showCategories;
    }

    public function showsCategoryDropdown(): ?bool
    {
        return $this->showCategoryDropdown;
    }
}
