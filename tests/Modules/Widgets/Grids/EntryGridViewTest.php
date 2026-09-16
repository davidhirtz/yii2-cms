<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Stringable;
use Yii;

class EntryGridViewTest extends TestCase
{
    public function testNoDropdownIsRenderedForASingleTenant(): void
    {
        Tenant::deleteAll(['!=', 'id', TenantCollection::getDefault()->id]);
        TenantCollection::invalidateCache();

        self::assertNull($this->createGrid()->tenantDropdown());
    }

    public function testDropdownIsRenderedForSeveralTenants(): void
    {
        self::assertGreaterThan(1, count(TenantCollection::getAll()));
        self::assertNotNull($this->createGrid()->tenantDropdown());
    }

    /**
     * The children of an entry are all its tenant's, so the filter could only empty the grid.
     */
    public function testNoDropdownIsRenderedForTheChildrenOfAnEntry(): void
    {
        self::assertGreaterThan(1, count(TenantCollection::getAll()));
        self::assertNull($this->createGrid(Entry::create())->tenantDropdown());
    }

    private function createGrid(?Entry $parent = null): TestEntryGridView
    {
        $provider = Yii::$container->get(EntryActiveDataProvider::class, config: [
            'parent' => $parent,
        ]);

        return TestEntryGridView::make()->provider($provider);
    }
}

/**
 * @extends EntryGridView<Entry>
 */
class TestEntryGridView extends EntryGridView
{
    public function tenantDropdown(): ?Stringable
    {
        return $this->getTenantDropdown();
    }
}
