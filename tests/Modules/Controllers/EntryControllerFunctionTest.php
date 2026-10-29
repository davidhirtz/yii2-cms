<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class EntryControllerFunctionTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    public function testIndexAsGuest(): void
    {
        $this->open('/admin/cms/entry/index');
        self::assertCurrentUrlEquals('https://www.test.localhost/admin/account/login?returnUrl='
            . urlencode('https://www.test.localhost/admin/cms/entry/index'));
    }

    public function testIndexWithoutPermission(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->getWebUser()->login($user);

        $this->open('/admin/cms/entry/index');
        self::assertResponseStatusCodeSame(403);
    }

    public function testIndexWithPermission(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->getWebUser()->login($user);

        $this->open('/admin/cms/entry/index');
        self::assertResponseIsSuccessful();
    }

    /**
     * `getAdminRoute()` returns an array, so it is spread into the redirect route, which `Url::toRoute()` otherwise
     * failed on.
     */
    public function testUpdateRedirectsBackToTheEntry(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);
        $this->getWebUser()->login($user);

        $entry = TestEntry::create();
        $entry->name = 'Test';
        $entry->slug = 'before';
        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

        $this->open("/admin/cms/entry/update?id=$entry->id");
        self::assertResponseIsSuccessful();

        $this->submit('form', $this->prefixFormValues($entry, ['slug' => 'after']));

        self::assertResponseIsSuccessful();
        self::assertCurrentUrlEquals("https://www.test.localhost/admin/cms/entry/update?id=$entry->id");
        self::assertSame('after', $entry->getPermalinks()->one()->slug);
    }
}
