<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Types\BlockType;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class BlockGridViewTest extends TestCase
{
    use UserFixtureTrait;

    private const int TYPE_GALLERY = 2;

    public function testTheTypeDropdownIsHiddenWhileOnlyOneTypeIsDeclared(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/block/index');

        self::assertIsString($html);
        self::assertStringNotContainsString('?type=', $html);
    }

    public function testTheTypeDropdownOffersEveryDeclaredType(): void
    {
        $this->declareTypes();
        $this->login();

        $html = Yii::$app->runAction('admin/cms/block/index');

        self::assertIsString($html);
        self::assertStringContainsString('?type=' . Block::TYPE_DEFAULT, $html);
        self::assertStringContainsString('Teasers', $html);

        self::assertStringContainsString('?type=' . self::TYPE_GALLERY, $html);
        self::assertStringContainsString('Galleries', $html);
    }

    public function testTheGridIsFilteredByTheType(): void
    {
        $this->declareTypes();
        $this->login();

        $this->createBlock('Teaser Block', Block::TYPE_DEFAULT);
        $this->createBlock('Gallery Block', self::TYPE_GALLERY);

        $html = Yii::$app->runAction('admin/cms/block/index', ['type' => self::TYPE_GALLERY]);

        self::assertIsString($html);
        self::assertStringContainsString('Gallery Block', $html);
        self::assertStringNotContainsString('Teaser Block', $html);
    }

    private function declareTypes(): void
    {
        Yii::$container->set(Block::class, [
            'types' => fn (): array => [
                BlockType::make(Block::TYPE_DEFAULT)
                    ->name('Teaser')
                    ->plural('Teasers'),
                BlockType::make(self::TYPE_GALLERY)
                    ->name('Gallery')
                    ->plural('Galleries'),
            ],
        ]);
    }

    private function createBlock(string $name, int $type): Block
    {
        $block = Block::instantiateByType($type);
        $block->loadDefaultValues();
        $block->type = $type;
        $block->name = $name;

        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        return $block;
    }

    private function login(): User
    {
        Block::getModule()->enableBlocks = true;

        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Block::AUTH_BLOCK);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
