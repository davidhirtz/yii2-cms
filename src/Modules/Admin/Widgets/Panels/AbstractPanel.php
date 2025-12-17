<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\LinkButtonTrait;
use Hirtz\Media\Modules\Admin\Widgets\Forms\Traits\DuplicateButtonTrait;
use Hirtz\Skeleton\Widgets\Panels\Panel;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

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
