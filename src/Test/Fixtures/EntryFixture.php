<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Skeleton\Test\Fixtures\ActiveFixture;
use Hirtz\Tenant\Test\Fixtures\TenantFixture;
use yii\test\Fixture;

class EntryFixture extends ActiveFixture
{
    public $modelClass = TestEntry::class;

    /**
     * @var list<class-string<Fixture>>
     */
    public $depends = [
        TenantFixture::class,
    ];
}
