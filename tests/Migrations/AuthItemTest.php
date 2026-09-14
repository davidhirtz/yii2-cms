<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Module;
use Hirtz\Media\Models\File;
use Hirtz\Media\Models\Folder;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module as AdminModule;
use Hirtz\Skeleton\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;
use yii\rbac\Item;
use yii\rbac\Role;

class AuthItemTest extends TestCase
{
    public function testAnAuthorEditsContentAndTheMediaLibrary(): void
    {
        $permissions = Yii::$app->getAuthManager()->getPermissionsByRole(Module::AUTH_ROLE_AUTHOR);

        self::assertSame(
            [Category::AUTH_CATEGORY, Entry::AUTH_ENTRY, File::AUTH_FILE, Folder::AUTH_FOLDER],
            $this->getSortedNames($permissions),
        );
    }

    /**
     * @param non-empty-string $role
     */
    #[DataProvider('roleDataProvider')]
    public function testARoleListsPermissionsRatherThanOtherRoles(string $role): void
    {
        $children = Yii::$app->getAuthManager()->getChildren($role);

        self::assertNotEmpty($children);
        self::assertSame([], array_filter($children, static fn (Item $item) => $item instanceof Role));
    }

    /**
     * @return list<array{non-empty-string}>
     */
    public static function roleDataProvider(): array
    {
        return [
            [User::AUTH_ROLE_ADMIN],
            [User::AUTH_ROLE_MANAGER],
            [Module::AUTH_ROLE_AUTHOR],
        ];
    }

    public function testAnAdministratorHoldsEveryPermissionAndAManagerAllButTheSystemOne(): void
    {
        $auth = Yii::$app->getAuthManager();
        $permissions = $this->getSortedNames($auth->getPermissions());

        self::assertContains(AdminModule::AUTH_SYSTEM, $permissions);
        self::assertSame($permissions, $this->getSortedNames($auth->getPermissionsByRole(User::AUTH_ROLE_ADMIN)));

        self::assertSame(
            array_values(array_diff($permissions, [AdminModule::AUTH_SYSTEM])),
            $this->getSortedNames($auth->getPermissionsByRole(User::AUTH_ROLE_MANAGER)),
        );
    }

    public function testTheMediaRoleIsGone(): void
    {
        self::assertNull(Yii::$app->getAuthManager()->getRole('media'));
    }

    /**
     * @param array<string, object> $items
     * @return list<string>
     */
    private function getSortedNames(array $items): array
    {
        $names = array_keys($items);
        sort($names);

        return $names;
    }
}
