<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Media\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class EntryControllerFunctionTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    public function testIndexAsGuest(): void
    {
        $this->open('/admin/cms/entry/index');
        self::assertCurrentUrlEquals('https://www.test.localhost/admin/account/login');
    }

    public function testIndexWithoutPermission(): void
    {
        $user = $this->getUserFromFixture('admin');
        Yii::$app->getUser()->login($user);

        $this->open('/admin/cms/entry/index');
        self::assertResponseStatusCodeSame(403);
    }

    public function testIndexWithPermission(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        Yii::$app->getUser()->login($user);

        $this->open('/admin/cms/entry/index');
        self::assertResponseIsSuccessful();
    }

    /**
     * Renaming an entry redirects back to it. `getAdminRoute()` returns an array, so it has to be spread into the
     * redirect route; appending it made the route element itself an array and `Url::toRoute()` failed on it.
     */
    public function testUpdateRedirectsBackToTheEntry(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);
        Yii::$app->getUser()->login($user);

        $entry = TestEntry::create();
        $entry->name = 'Test';
        $entry->slug = 'before';
        self::assertTrue($entry->save());

        $this->open("/admin/cms/entry/update?id=$entry->id");
        self::assertResponseIsSuccessful();

        $this->submit('form', $this->prefixFormValues($entry, ['slug' => 'after']));

        self::assertResponseIsSuccessful();
        self::assertCurrentUrlEquals("https://www.test.localhost/admin/cms/entry/update?id=$entry->id");
        self::assertSame('after', $entry->getPermalinks()->one()->slug);
    }
}
