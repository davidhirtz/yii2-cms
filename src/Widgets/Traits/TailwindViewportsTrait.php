<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets\Traits;

use Hirtz\Media\Models\Asset;

trait TailwindViewportsTrait
{
    /**
     * @var array<string, list<int>>
     */
    protected array $viewports = [
        'sm:hidden' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_MOBILE],
        'hidden sm:block' => [Asset::TYPE_DEFAULT, Asset::TYPE_VIEWPORT_DESKTOP],
    ];
}
