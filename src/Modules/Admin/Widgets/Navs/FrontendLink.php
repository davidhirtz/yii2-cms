<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\Traits\FrontendUrlTrait;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class FrontendLink extends Widget
{
    use FrontendUrlTrait;
    use UrlTrait;

    protected function renderContent(): string|Stringable
    {
        return A::make()
            ->class($this->isDisabled() ? 'text-invalid' : null)
            ->text($this->url)
            ->href($this->url)
            ->target('_blank')
            ->render();
    }
}
