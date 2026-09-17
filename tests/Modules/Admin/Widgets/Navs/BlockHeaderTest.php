<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use Yii;

/**
 * The header adds no dropdown of its own, so a view handing it one renders exactly one (monorepo issue #144).
 */
class BlockHeaderTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;
    }

    public function testTheUpdatePageRendersTheBlockActionDropdownOnce(): void
    {
        $this->login();
        $block = $this->createBlock();

        $html = Yii::$app->runAction('admin/cms/block/update', ['id' => $block->id]);

        self::assertIsString($html);
        self::assertSame(1, substr_count($html, 'dropdown-actions'));
        self::assertStringContainsString("/admin/cms/block/delete?id=$block->id", $html);
    }

    public function testTheAssetIndexRendersTheAssetDropdownAlone(): void
    {
        $this->login();
        $block = $this->createBlock();

        $html = Yii::$app->runAction('admin/cms/block-asset/index', ['block' => $block->id]);

        self::assertIsString($html);
        self::assertSame(1, substr_count($html, 'dropdown-actions'));
        self::assertStringNotContainsString("/admin/cms/block/delete?id=$block->id", $html);
    }

    private function createBlock(): Block
    {
        $block = Block::create();
        $block->name = 'Header Block';

        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        return $block;
    }

    private function login(): void
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');
        $user = User::findOne($fixture->data['admin']['id']);

        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission(Block::AUTH_BLOCK), $user->id);

        $this->getWebUser()->setIdentity($user);
    }
}
