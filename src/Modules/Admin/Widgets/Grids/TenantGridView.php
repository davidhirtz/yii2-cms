<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Actions\UpdateTenantEntryCount;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Modules\Admin\Data\TenantActiveDataProvider;
use Override;
use Yii;

/**
 * @extends \Hirtz\Tenant\Modules\Admin\Widgets\Grids\TenantGridView<TenantActiveDataProvider>
 */
class TenantGridView extends \Hirtz\Tenant\Modules\Admin\Widgets\Grids\TenantGridView
{
    #[Override]
    protected function configure(): void
    {
        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getNameColumn(),
            $this->getEntryCountColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    /**
     * @return Column<Tenant>|null
     */
    protected function getEntryCountColumn(): ?Column
    {
        /** @var BadgeColumn<Tenant> $column */
        $column = BadgeColumn::make();

        return $column
            ->property(UpdateTenantEntryCount::ENTRY_COUNT_ATTRIBUTE)
            ->title(Yii::t('cms', 'ENTRY_PLURAL'))
            // Another tenant's entries are listed by its own admin, which the link switches to.
            ->url(fn (Tenant $tenant) => ['/admin/cms/entry/index', 'tenant' => $tenant])
            ->link(fn (A|Div $tag) => $tag instanceof A ? $tag->attribute('hx-boost', 'false') : $tag);
    }
}
