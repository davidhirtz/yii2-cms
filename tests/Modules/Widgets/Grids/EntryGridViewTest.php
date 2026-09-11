<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Grids;

use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Stringable;

class EntryGridViewTest extends TestCase
{
    public function testNoDropdownIsRenderedForASingleTenant(): void
    {
        Tenant::deleteAll(['!=', 'id', TenantCollection::getDefault()->id]);
        TenantCollection::invalidateCache();

        self::assertNull(TestEntryGridView::make()->tenantDropdown());
    }

    public function testDropdownIsRenderedForSeveralTenants(): void
    {
        self::assertGreaterThan(1, count(TenantCollection::getAll()));
        self::assertNotNull(TestEntryGridView::make()->tenantDropdown());
    }
}

class TestEntryGridView extends EntryGridView
{
    public function tenantDropdown(): ?Stringable
    {
        return $this->getTenantDropdown();
    }
}
