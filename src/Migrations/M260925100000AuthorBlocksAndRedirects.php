<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Module;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Redirect;
use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260925100000AuthorBlocksAndRedirects extends Migration
{
    use MigrationTrait;

    #[Override]
    public function safeUp(): void
    {
        $auth = $this->getAuthManager();
        $author = $auth->getRole(Module::AUTH_ROLE_AUTHOR);

        if ($author) {
            foreach ([Block::AUTH_BLOCK, Redirect::AUTH_REDIRECT] as $name) {
                if ($permission = $auth->getPermission($name)) {
                    $this->addChildIfMissing($author, $permission);
                }
            }

            $auth->invalidateCache();
        }
    }

    #[Override]
    public function safeDown(): void
    {
        $auth = $this->getAuthManager();
        $author = $auth->getRole(Module::AUTH_ROLE_AUTHOR);

        if ($author) {
            foreach ([Block::AUTH_BLOCK, Redirect::AUTH_REDIRECT] as $name) {
                if ($permission = $auth->getPermission($name)) {
                    $auth->removeChild($author, $permission);
                }
            }

            $auth->invalidateCache();
        }
    }
}
