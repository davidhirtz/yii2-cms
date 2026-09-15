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

    public function showsCategories(): ?bool
    {
        return $this->showCategories;
    }

    public function showsCategoryDropdown(): ?bool
    {
        return $this->showCategoryDropdown;
    }
}
