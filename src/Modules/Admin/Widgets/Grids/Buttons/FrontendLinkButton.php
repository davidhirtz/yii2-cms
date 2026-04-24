<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons;

use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\Traits\FrontendUrlTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;

class FrontendLinkButton extends Button
{
    use FrontendUrlTrait;
}
