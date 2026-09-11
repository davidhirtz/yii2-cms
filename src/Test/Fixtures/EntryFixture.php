<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Skeleton\Test\Fixtures\ActiveFixture;
use Hirtz\Tenant\Test\Fixtures\TenantFixture;

class EntryFixture extends ActiveFixture
{
    public $modelClass = TestEntry::class;

    public $depends = [
        TenantFixture::class,
    ];
}
