<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Events;

use Hirtz\Cms\Models\Entry;
use Hirtz\Tenant\Models\Tenant;
use yii\base\ModelEvent;

readonly class TenantBeforeDeleteEventHandler
{
    public function __construct(protected ModelEvent $event, protected Tenant $tenant)
    {
        $this->handleEvent();
    }

    protected function handleEvent(): void
    {
        $this->event->isValid = !Entry::find()
            ->where([Entry::tableName() . '.[[tenant_id]]' => $this->tenant->id])
            ->exists();
    }
}
