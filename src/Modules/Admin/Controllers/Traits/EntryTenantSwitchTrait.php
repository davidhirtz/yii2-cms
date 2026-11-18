<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers\Traits;

use yii\base\Action;

trait EntryTenantSwitchTrait
{
    /**
     * Every record here belongs to one entry, and so to its tenant.
     *
     * @return array<int|string, mixed>|null
     */
    public function getTenantSwitchRoute(Action $action): ?array
    {
        return ['/' . $action->controller->module->getUniqueId() . '/entry/index'];
    }
}
