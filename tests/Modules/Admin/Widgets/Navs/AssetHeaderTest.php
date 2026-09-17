<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Yii;

/**
 * The media bundle owns `AssetHeader` but not the routes an asset page lives on, so the chain it walks is
 * exercised here, where an entry and a section have assets.
 */
class AssetHeaderTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheEntryAssetPageIsTitledWithTheAsset(): void
    {
        $this->login();
        $asset = $this->getAssetFromFixture('entry-asset');

        $html = Yii::$app->runAction('admin/cms/entry-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString('>' . $asset->getAdminName() . '</a></h1>', $html);
        self::assertStringNotContainsString($asset->model->getAdminName() . '</a></h1>', $html);
    }

    public function testTheEntryAssetPathHoldsTheEntry(): void
    {
        $this->login();
        $asset = $this->getAssetFromFixture('entry-asset');

        $html = Yii::$app->runAction('admin/cms/entry-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<a class="header-path-link" href="/admin/cms/entry/update?id=' . $asset->model_id . '">',
            $html,
        );
    }

    public function testTheSectionAssetPathHoldsTheEntryAndTheSection(): void
    {
        $this->login();
        $asset = $this->getAssetFromFixture('section-image-1');
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<a class="header-path-link" href="/admin/cms/entry/update?id=' . $section->entry_id . '">',
            $html,
        );
        self::assertStringContainsString(
            '<a class="header-path-link" href="/admin/cms/section/update?id=' . $section->id . '">',
            $html,
        );
    }

    public function testTheSectionAssetPageStillRendersTheSectionSubmenu(): void
    {
        $this->login();
        $asset = $this->getAssetFromFixture('section-image-1');
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString('/admin/cms/section/update?id=' . $section->id, $html);
        self::assertStringContainsString('/admin/cms/section-asset/index?section=' . $section->id, $html);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission(Entry::AUTH_ENTRY), $user->id);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }

    /**
     * `CmsFixtureTrait` declares the user fixture but no accessor for it: adding one would collide with
     * `UserFixtureTrait` for the tests that use both.
     */
    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
