<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\Traits\LinkButtonTrait;
use Hirtz\Media\Modules\Admin\Widgets\Panels\Traits\DuplicateButtonTrait;
use Hirtz\Skeleton\Widgets\Panels\Panel;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

/**
 * @template T of Asset|Category|Entry|Section
 * @property T $model
 */
abstract class AbstractPanel extends Widget
{
    use DuplicateButtonTrait;
    use ModelWidgetTrait;
    use LinkButtonTrait;

    protected function renderContent(): string|Stringable
    {
        return Panel::make()
            ->attribute('id', 'operations')
            ->buttons(...$this->getButtons());
    }

    abstract protected function getButtons(): array;
}
