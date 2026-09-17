<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryCreateButton;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\Application;

/**
 * A grid's type filter is a query parameter, so the button that creates the next record carries it through — it
 * used to overwrite it with the type a project pins the page to, which is normally nothing (monorepo issue #161).
 */
class EntryCreateButtonTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheFilteredTypeReachesTheCreateUrl(): void
    {
        $this->login();
        $this->getWebRequest()->setQueryParams(['type' => '2']);

        self::assertStringContainsString('type=2', (string)EntryCreateButton::make());
    }

    public function testThePinnedTypeIsTheFallback(): void
    {
        $this->login();
        Application::current()->getView()->params['entryType'] = 3;

        self::assertStringContainsString('type=3', (string)EntryCreateButton::make());
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Entry::AUTH_ENTRY);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
