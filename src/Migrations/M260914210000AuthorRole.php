<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Media\Models\File;
use Hirtz\Media\Models\Folder;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Cms\Module;
use yii\db\Migration;

/**
 * `author` is an editor's role, so it is no longer something `admin` holds — which now lists the permissions
 * themselves — and it gains the two media permissions the dropped `media` role used to carry.
 *
 * @noinspection PhpUnused
 */
class M260914210000AuthorRole extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $auth = $this->getAuthManager();
        $author = $auth->getRole(Module::AUTH_ROLE_AUTHOR);

        $auth->removeChild($auth->getRole(User::AUTH_ROLE_ADMIN), $author);

        $auth->addChild($author, $auth->getPermission(File::AUTH_FILE));
        $auth->addChild($author, $auth->getPermission(Folder::AUTH_FOLDER));

        $auth->invalidateCache();
    }

    public function safeDown(): void
    {
        $auth = $this->getAuthManager();
        $author = $auth->getRole(Module::AUTH_ROLE_AUTHOR);

        $auth->removeChild($author, $auth->getPermission(File::AUTH_FILE));
        $auth->removeChild($author, $auth->getPermission(Folder::AUTH_FOLDER));

        $auth->addChild($auth->getRole(User::AUTH_ROLE_ADMIN), $author);
        $auth->invalidateCache();
    }
}
