<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use Yii;
use yii\web\NotFoundHttpException;

class BlockEntryControllerTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;
        $module->enableBlockEntries = true;
    }

    public function testCreateLinksTheEntryToTheBlock(): void
    {
        $this->login();
        $block = $this->createBlock();

        $this->post('admin/cms/block-entry/create', ['block' => $block->id, 'entry' => 1]);

        self::assertNotNull(BlockEntry::findOne(['model_id' => $block->id, 'entry_id' => 1]));
        self::assertSame(1, Block::findOne($block->id)->entry_count);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testDeleteRemovesTheLink(): void
    {
        $this->login();
        $block = $this->createBlock();

        $blockEntry = BlockEntry::create();
        $blockEntry->populateModelRelation($block);
        $blockEntry->populateEntryRelation(TestEntry::findOne(1));
        self::assertTrue($blockEntry->insert(), print_r($blockEntry->getErrors(), true));

        $this->post('admin/cms/block-entry/delete', ['block' => $block->id, 'entry' => 1]);

        self::assertNull(BlockEntry::findOne($blockEntry->id));
        self::assertSame(0, Block::findOne($block->id)->entry_count);
    }

    /**
     * The tab is only offered for a block that links entries, so the route refuses one that does not.
     */
    public function testIndexIsNotFoundWhileTheFeatureIsOff(): void
    {
        $this->login();
        $block = $this->createBlock();

        TestEntry::getModule()->enableBlockEntries = false;

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/block-entry/index', ['block' => $block->id]);
    }

    private function createBlock(): Block
    {
        $block = Block::create();
        $block->name = 'Linking Block';

        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        return $block;
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = [], array $bodyParams = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $permission = Yii::$app->getAuthManager()->getPermission(Block::AUTH_BLOCK);
        Yii::$app->getAuthManager()->assign($permission, $user->id);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }

    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
