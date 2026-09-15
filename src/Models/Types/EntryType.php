<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use Hirtz\Cms\Models\Traits\FooterAttributeTrait;
use Hirtz\Cms\Models\Traits\MenuAttributeTrait;
use Hirtz\Media\Models\Interfaces\AssetModelTypeInterface;
use Hirtz\Media\Models\Types\Traits\AssetModelTypeTrait;

/**
 * `showInMenu` and `showInFooter` are read by {@see MenuAttributeTrait} and {@see FooterAttributeTrait}, which an
 * entry opts into: an option a model does not consult is inert, and a type trait per model trait is more classes
 * than options.
 */
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
    protected bool $showInMenu = true;
    protected bool $showInFooter = true;

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

    public function showInMenu(bool $showInMenu = true): static
    {
        $this->showInMenu = $showInMenu;
        return $this;
    }

    public function showInFooter(bool $showInFooter = true): static
    {
        $this->showInFooter = $showInFooter;
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

    public function showsCategories(): ?bool
    {
        return $this->showCategories;
    }

    public function showsCategoryDropdown(): ?bool
    {
        return $this->showCategoryDropdown;
    }

    public function hasShowInMenuEnabled(): bool
    {
        return $this->showInMenu;
    }

    public function hasShowInFooterEnabled(): bool
    {
        return $this->showInFooter;
    }
}
