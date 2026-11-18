<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Modules\Admin\Widgets\Buttons\TenantDropdownButton;
use Hirtz\Tenant\Test\Fixtures\TenantFixture;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Yii;

final class TenantDropdownButtonTest extends TestCase
{
    use UserFixtureTrait;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function fixtures(): array
    {
        return [
            'tenant' => TenantFixture::class,
            'user' => UserFixture::class,
        ];
    }

    public function testAnAuthorSwitchesTheTenant(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Entry::AUTH_ENTRY);
        $this->getWebUser()->setIdentity($user);

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);
        $manager->setTenant(TenantCollection::getDefault() ?? self::fail('No tenant.'));

        self::assertStringContainsString('German Tenant', TenantDropdownButton::make()->render());
    }
}
