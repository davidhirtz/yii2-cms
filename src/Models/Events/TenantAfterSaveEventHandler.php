<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Events;

use Hirtz\Cms\Modules\ModuleTrait;

readonly class TenantAfterSaveEventHandler
{
    use ModuleTrait;

    public function __construct()
    {
        $this->handleEvent();
    }

    protected function handleEvent(): void
    {
        static::getModule()->invalidatePageCache();
    }
}
