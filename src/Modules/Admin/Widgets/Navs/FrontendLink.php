<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\Traits\FrontendUrlTrait;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class FrontendLink extends Widget
{
    use FrontendUrlTrait;
    use TagAttributesTrait;
    use UrlTrait;

    #[Override]
    protected function configure(): void
    {
        $this->configureDefaultUrl();

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return A::make()
            ->attributes($this->attributes)
            ->addClass($this->isDisabled() ? 'text-invalid' : null)
            ->text($this->url)
            ->href($this->url)
            ->target('_blank')
            ->render();
    }
}
