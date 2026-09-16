<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class BlockControllerTest extends TestCase
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

    public function testIndexListsTheBlocks(): void
    {
        $this->login();
        $this->createBlock('Listed Block');

        $html = Yii::$app->runAction('admin/cms/block/index');

        self::assertIsString($html);
        self::assertStringContainsString('Listed Block', $html);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/cms/block/index');
    }

    public function testCreateInsertsTheBlockAndRedirectsToItsUpdatePage(): void
    {
        $this->login();

        $response = $this->post('admin/cms/block/create', bodyParams: [
            'Block' => ['name' => 'Created Block'],
        ]);

        self::assertInstanceOf(Response::class, $response);

        $block = Block::findOne(['name' => 'Created Block']);
        self::assertNotNull($block);
        self::assertStringContainsString("id=$block->id", (string)$response->getHeaders()->get('location'));
    }

    public function testUpdateSavesTheName(): void
    {
        $this->login();
        $block = $this->createBlock('Before');

        $this->post('admin/cms/block/update', ['id' => $block->id], [
            'Block' => ['name' => 'After'],
        ]);

        self::assertSame('After', Block::findOne($block->id)->name);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testUpdateOfAnUnknownBlockIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/block/update', ['id' => 99999]);
    }

    public function testDeleteRemovesTheBlock(): void
    {
        $this->login();
        $block = $this->createBlock('Doomed');

        $this->post('admin/cms/block/delete', ['id' => $block->id]);

        self::assertNull(Block::findOne($block->id));
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testOrderRewritesThePositions(): void
    {
        $this->login();

        $first = $this->createBlock('First');
        $second = $this->createBlock('Second');

        self::assertLessThan($second->position, $first->position);

        $this->post('admin/cms/block/order', bodyParams: [
            'block' => [$second->id, $first->id],
        ]);

        self::assertLessThan(
            Block::findOne($first->id)->position,
            Block::findOne($second->id)->position
        );
    }

    private function createBlock(string $name): Block
    {
        $block = Block::create();
        $block->name = $name;

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
