<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\BlockIdSelectField;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class BlockIdSelectFieldTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Section::getModule()->enableBlocks = true;

        Yii::$container->set(Section::class, ['types' => static fn (): array => [
            SectionType::make(Section::TYPE_DEFAULT)
                ->name('Block')
                ->allowBlock(),
        ]]);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        parent::tearDown();
    }

    public function testTheButtonOpensTheSelectedBlock(): void
    {
        $this->login(true);

        $block = $this->createBlock();
        $other = $this->createBlock();

        $section = $this->createSection();
        $section->block_id = $block->id;

        $html = $this->render($section);

        self::assertStringContainsString('<div class="form-action">', $html);
        self::assertStringContainsString('data-select-link', $html);
        self::assertStringContainsString("data-url=\"/admin/cms/block/update?id=$other->id\"", $html);
        self::assertMatchesRegularExpression(
            "#<a[^>]+href=\"/admin/cms/block/update\?id=$block->id\"[^>]+target=\"_blank\"#",
            $html
        );
        self::assertStringNotContainsString('hidden', $html);
    }

    /**
     * The script shows the button once a block is picked, so it is rendered hidden rather than left out.
     */
    public function testTheButtonIsHiddenWithoutABlock(): void
    {
        $this->login(true);
        $this->createBlock();

        $html = $this->render($this->createSection());

        self::assertMatchesRegularExpression('#<a[^>]+hidden#', $html);
    }

    public function testNoButtonWithoutTheBlockPermission(): void
    {
        $this->login(false);
        $block = $this->createBlock();

        $section = $this->createSection();
        $section->block_id = $block->id;

        $html = $this->render($section);

        self::assertStringContainsString('<select', $html);
        self::assertStringNotContainsString('form-action', $html);
        self::assertStringNotContainsString('data-url', $html);
    }

    private function createSection(): Section
    {
        $section = Section::create();
        $section->type = Section::TYPE_DEFAULT;

        return $section;
    }

    private function createBlock(): Block
    {
        $block = Block::create();
        $block->name = 'Placed';

        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        return $block;
    }

    private function login(bool $canEditBlocks): void
    {
        $user = $this->getUserFromFixture('admin');

        if ($canEditBlocks) {
            $this->assignPermission($user->id, Block::AUTH_BLOCK);
        }

        $this->getWebUser()->setIdentity($user);
    }

    private function render(Section $section): string
    {
        return BlockIdSelectField::make()
            ->model($section)
            ->render();
    }
}
