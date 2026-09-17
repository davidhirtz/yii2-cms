<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Module;
use Hirtz\Media\Models\File;
use Hirtz\Media\Models\Folder;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module as AdminModule;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Tenant\Models\Tenant;
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

    /**
     * The installation-level permissions are the administrator's alone: inspecting the server and its error
     * logs, and the tenants the whole installation is cut into.
     */
    public function testAnAdministratorHoldsEveryPermissionAndAManagerAllButTheInstallationLevelOnes(): void
    {
        $auth = Yii::$app->getAuthManager();
        $permissions = $this->getSortedNames($auth->getPermissions());
        $adminOnly = [AdminModule::AUTH_SYSTEM, Tenant::AUTH_TENANT];

        self::assertEmpty(array_diff($adminOnly, $permissions));
        self::assertSame($permissions, $this->getSortedNames($auth->getPermissionsByRole(User::AUTH_ROLE_ADMIN)));

        self::assertSame(
            array_values(array_diff($permissions, $adminOnly)),
            $this->getSortedNames($auth->getPermissionsByRole(User::AUTH_ROLE_MANAGER)),
        );
    }

    public function testTheMediaRoleIsGone(): void
    {
        self::assertNull(Yii::$app->getAuthManager()->getRole('media'));
    }

    /**
     * A permission nobody registered is not an error anywhere: the record simply stops being reachable, and every
     * link to it silently disappears.
     *
     * @param class-string<AdminModelInterface> $class
     */
    #[DataProvider('adminModelDataProvider')]
    public function testAnAdminModelIsGuardedByARegisteredPermission(string $class, string $permission): void
    {
        $model = Yii::createObject($class);

        self::assertSame($permission, $model->getPermissionName());
        self::assertNotNull(Yii::$app->getAuthManager()->getPermission($permission));
    }

    /**
     * A model only ever edited through another answers that one's permission — there is no `entryAsset`.
     *
     * @return array<string, array{class-string<AdminModelInterface>, string}>
     */
    public static function adminModelDataProvider(): array
    {
        return [
            'block' => [Block::class, Block::AUTH_BLOCK],
            'block asset' => [BlockAsset::class, Block::AUTH_BLOCK],
            'block entry' => [BlockEntry::class, Block::AUTH_BLOCK],
            'category' => [Category::class, Category::AUTH_CATEGORY],
            'entry' => [Entry::class, Entry::AUTH_ENTRY],
            'entry asset' => [EntryAsset::class, Entry::AUTH_ENTRY],
            'entry category' => [EntryCategory::class, Entry::AUTH_ENTRY],
            'file' => [File::class, File::AUTH_FILE],
            'folder' => [Folder::class, Folder::AUTH_FOLDER],
            'redirect' => [Redirect::class, Redirect::AUTH_REDIRECT],
            'section' => [Section::class, Entry::AUTH_ENTRY],
            'section asset' => [SectionAsset::class, Entry::AUTH_ENTRY],
            'section entry' => [SectionEntry::class, Entry::AUTH_ENTRY],
            'tenant' => [Tenant::class, Tenant::AUTH_TENANT],
            'user' => [User::class, User::AUTH_USER],
        ];
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
