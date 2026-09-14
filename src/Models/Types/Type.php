<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Types;

use Hirtz\Cms\Models\ActiveRecord;

/**
 * The two options {@see ActiveRecord} reads for every cms model.
 */
class Type extends \Hirtz\Skeleton\Models\Types\Type
{
    protected ?string $viewFile = null;
    protected ?string $cssClass = null;

    public function viewFile(?string $viewFile): static
    {
        $this->viewFile = $viewFile;
        return $this;
    }

    public function cssClass(?string $cssClass): static
    {
        $this->cssClass = $cssClass;
        return $this;
    }

    public function getViewFile(): ?string
    {
        return $this->viewFile;
    }

    public function getCssClass(): string
    {
        return $this->cssClass ?? '';
    }
}
