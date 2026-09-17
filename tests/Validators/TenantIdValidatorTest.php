<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Validators;

use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;

class TenantIdValidatorTest extends TestCase
{
    public function testEmptyValueResolvesToTheDefaultTenant(): void
    {
        $entry = TestEntry::create();
        $entry->name = 'No tenant';

        self::assertTrue($entry->validate(), implode(' ', $entry->getErrorSummary(true)));
        self::assertSame(TenantCollection::getDefault()->id, $entry->tenant_id);
        self::assertSame(TenantCollection::getDefault()->id, $entry->tenant->id);
    }

    /**
     * A form reload renders the loaded record without validating it, so the posted string has to be gone by then:
     * {@see \Hirtz\Cms\Models\Entry::getTenantRouteParams()} hands the value to a `?int` parameter.
     */
    public function testAPostedTenantIdIsAnIntegerAfterLoad(): void
    {
        $entry = TestEntry::create();
        $tenantId = (string)TenantCollection::getDefault()->id;

        self::assertTrue($entry->load(['Entry' => ['name' => 'Loaded', 'tenant_id' => $tenantId]]));
        self::assertSame((int)$tenantId, $entry->tenant_id);
        self::assertSame(TenantCollection::getDefault(), $entry->getTenantRouteParams()['tenant']);
    }

    public function testUnknownTenantIsRejected(): void
    {
        $entry = TestEntry::create();
        $entry->name = 'Invalid';
        $entry->tenant_id = 12345;

        self::assertFalse($entry->validate());
        self::assertArrayHasKey('tenant_id', $entry->getErrors());
    }

    public function testKnownTenantPopulatesTheRelation(): void
    {
        $tenant = TenantCollection::getAll()[2];

        $entry = TestEntry::create();
        $entry->name = 'Second tenant';
        $entry->tenant_id = $tenant->id;

        self::assertTrue($entry->validate(), implode(' ', $entry->getErrorSummary(true)));
        self::assertSame($tenant->id, $entry->tenant->id);
    }
}
