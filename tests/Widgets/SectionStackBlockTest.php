<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\BlockSectionType;
use Hirtz\Cms\Models\Types\BlockType;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\SectionStack;
use Override;
use Yii;

class SectionStackBlockTest extends TestCase
{
    private int $sectionId = 0;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::setAlias('@cmsTestViews', dirname(__DIR__) . '/data/views');
        $this->sectionId = 0;

        $module = Section::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;
        $module->enableBlockEntries = true;
    }

    public function testABlockSectionRendersTheBlocksViewFile(): void
    {
        $section = $this->createSection(BlockSection::TYPE_BLOCK);
        $section->populateBlockRelation($this->createBlock(BlockSectionBlock::TYPE_ALT));

        self::assertStringStartsWith('<a view="_alt"', (string)$this->createStack([$section]));
    }

    public function testABlockSectionWithoutABlockIsDropped(): void
    {
        $stack = $this->createStack([
            $this->createSection(BlockSection::TYPE_BLOCK),
            $this->createSection(BlockSection::TYPE_TEXT),
        ]);

        self::assertSame(
            '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[2:1]</g>',
            (string)$stack
        );
    }

    public function testASectionWhoseTypeAllowsNoBlockKeepsItsOwnViewFile(): void
    {
        $section = $this->createSection(BlockSection::TYPE_TEXT);
        $section->populateBlockRelation($this->createBlock(BlockSectionBlock::TYPE_ALT));

        self::assertNull($section->getVisibleBlock());
        self::assertStringStartsWith('<g view="_sections"', (string)$this->createStack([$section]));
    }

    public function testTheModuleFlagDecidesBeforeTheType(): void
    {
        Section::getModule()->enableBlocks = false;

        $section = $this->createSection(BlockSection::TYPE_BLOCK);

        self::assertFalse($section->allowsBlock());
        self::assertStringStartsWith('<g view="_sections"', (string)$this->createStack([$section]));
    }

    /**
     * @param Section[] $sections
     */
    private function createStack(array $sections): SectionStack
    {
        return SectionStack::make()
            ->viewFile('@cmsTestViews/site/_sections')
            ->sections($sections);
    }

    private function createSection(int $type): BlockSection
    {
        $section = BlockSection::instantiateByType($type);
        $section->id = ++$this->sectionId;

        return $section;
    }

    private function createBlock(int $type): BlockSectionBlock
    {
        $block = BlockSectionBlock::instantiateByType($type);
        $block->id = 1;
        $block->name = 'Test Block';

        return $block;
    }
}

/**
 * Scratch models, declared here because a second class in a test file is only autoloadable while that file runs.
 */
class BlockSection extends Section
{
    public const int TYPE_TEXT = 1;
    public const int TYPE_BLOCK = 2;

    #[Override]
    public function getTypes(): array
    {
        return [
            SectionType::make(self::TYPE_TEXT)
                ->name('Text'),
            BlockSectionType::make(self::TYPE_BLOCK),
        ];
    }
}

class BlockSectionBlock extends Block
{
    public const int TYPE_ALT = 1;

    #[Override]
    public function getTypes(): array
    {
        return [
            BlockType::make(self::TYPE_ALT)
                ->name('Alt')
                ->viewFile('@cmsTestViews/site/_alt'),
        ];
    }
}
