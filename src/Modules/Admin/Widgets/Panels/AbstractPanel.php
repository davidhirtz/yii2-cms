<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\Traits\LinkButtonTrait;
use Hirtz\Skeleton\Widgets\Buttons\DuplicateButton;
use Hirtz\Skeleton\Widgets\Panels\Panel;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * @template T of Asset|Category|Entry|Section
 * @property T $model
 */
abstract class AbstractPanel extends Widget
{
    use LinkButtonTrait;

    protected Asset|Category|Entry|Section $model;

    public function model(Asset|Category|Entry|Section $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Panel::make()
            ->attribute('id', 'operations')
            ->buttons(...$this->getButtons());
    }

    protected function getDuplicateButton(): ?Stringable
    {
        return DuplicateButton::make()->model($this->model);
    }

    abstract protected function getButtons(): array;
}
