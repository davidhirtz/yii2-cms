<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
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

class BlockSectionControllerTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        TestEntry::getModule()->enableBlocks = true;
    }

    public function testIndexListsTheSectionsThatPlaceTheBlock(): void
    {
        $this->login();
        $block = $this->createBlock();
        $section = $this->placeBlock($block);

        $html = Yii::$app->runAction('admin/cms/block-section/index', ['block' => $block->id]);

        self::assertIsString($html);
        self::assertStringContainsString((string)$section->entry->getI18nAttribute('name'), $html);
        self::assertStringContainsString("block-section/delete?id=$section->id", $html);
    }

    public function testIndexWithoutABlockIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/block-section/index');
    }

    public function testDeleteRemovesTheSectionAndReturnsToTheBlock(): void
    {
        $this->login();
        $block = $this->createBlock();
        $section = $this->placeBlock($block);
        $this->placeBlock($block, 5);

        $response = $this->post('admin/cms/block-section/delete', ['id' => $section->id]);

        self::assertNull(Section::findOne($section->id));
        self::assertSame(1, Block::findOne($block->id)->section_count);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));

        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString(
            "block-section/index?block=$block->id",
            (string)$response->getHeaders()->get('location')
        );
    }

    /**
     * The tab is gone with the last section, so the redirect has to leave it.
     */
    public function testDeleteOfTheLastSectionReturnsToTheBlockItself(): void
    {
        $this->login();
        $block = $this->createBlock();
        $section = $this->placeBlock($block);

        $response = $this->post('admin/cms/block-section/delete', ['id' => $section->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString(
            "block/update?id=$block->id",
            (string)$response->getHeaders()->get('location')
        );
    }

    public function testDeleteOfASectionThatPlacesNoBlockIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->post('admin/cms/block-section/delete', ['id' => 6]);
    }

    /**
     * Listing the sections is the block's permission; deleting one is the entry's.
     */
    public function testDeleteWithoutTheEntryPermissionIsForbidden(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Block::AUTH_BLOCK);
        $this->getWebUser()->setIdentity($user);

        $block = $this->createBlock();
        $section = $this->placeBlock($block);

        $this->expectException(ForbiddenHttpException::class);
        $this->post('admin/cms/block-section/delete', ['id' => $section->id]);
    }

    private function createBlock(): Block
    {
        $block = Block::create();
        $block->name = 'Placed';

        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        return $block;
    }

    private function placeBlock(Block $block, int $sectionId = 6): Section
    {
        $section = Section::findOne($sectionId);
        $section->block_id = $block->id;

        self::assertNotFalse($section->update(false, ['block_id']));

        return $section;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $this->assignPermission($user->id, Block::AUTH_BLOCK);
        $this->assignPermission($user->id, Entry::AUTH_ENTRY);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }

    private function assignPermission(int $userId, string $permission): void
    {
        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission($permission), $userId);
    }

    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
