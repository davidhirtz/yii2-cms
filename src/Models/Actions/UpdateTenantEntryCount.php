<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Cms\Models\Entry;
use Hirtz\Tenant\Models\Tenant;

/**
 * The column is owned by this action, not by {@see Tenant}: the tenant model stays unaware of the cms.
 */
class UpdateTenantEntryCount
{
    final public const string ENTRY_COUNT_ATTRIBUTE = 'entry_count';

    public function __construct(
        protected int $tenantId,
    ) {
    }

    public function update(): void
    {
        $entryCount = Entry::find()
            ->where([Entry::tableName() . '.[[tenant_id]]' => $this->tenantId])
            ->count();

        Tenant::updateAll([
            self::ENTRY_COUNT_ATTRIBUTE => $entryCount,
            'updated_at' => new DateTime(),
        ], ['id' => $this->tenantId]);
    }
}
