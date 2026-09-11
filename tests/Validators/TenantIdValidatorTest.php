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
