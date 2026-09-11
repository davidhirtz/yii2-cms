<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test;

use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Test\Fixtures\TenantFixture;
use Override;

class TestCase extends \Hirtz\Skeleton\Test\TestCase
{
    /**
     * Every entry needs a tenant, so the fixture is loaded even for a test case that declares none of its own.
     */
    #[Override]
    public function globalFixtures(): array
    {
        return [
            'tenant' => TenantFixture::class,
        ];
    }

    #[Override]
    protected function setUp(): void
    {
        $this->config ??= require(__DIR__ . '/../../config/test.php');
        parent::setUp();

        TenantCollection::invalidateCache();
    }

    #[Override]
    protected function tearDown(): void
    {
        TenantCollection::invalidateCache();
        parent::tearDown();
    }
}
