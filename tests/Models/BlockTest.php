<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Actions\DeleteSections;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\BlockSectionType;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;
use Yii;

class BlockTest extends TestCase
{
    use CmsFixtureTrait;

    private const int TYPE_BLOCK = 2;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;
        $module->enableBlockEntries = true;

        // The container is how a project declares types without subclassing, which is what a block section needs.
        Yii::$container->set(Section::class, [
            'types' => fn (): array => [
                SectionType::make(Section::TYPE_DEFAULT)
                    ->name('Default'),
                BlockSectionType::make(self::TYPE_BLOCK),
            ],
        ]);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        parent::tearDown();
    }

    public function testTheNameIsRequired(): void
    {
        $block = Block::create();

        self::assertFalse($block->insert());
        self::assertArrayHasKey('name', $block->getErrors());
    }

    public function testABlockIsNotTiedToAnEntry(): void
    {
        $block = $this->createBlock();

        self::assertNotEmpty($block->id);
        self::assertFalse($block->getRoute());
        self::assertSame([], $block->getVisibleEntries());
    }

    public function testDeletingABlockLeavesItsSectionsInPlace(): void
    {
        $block = $this->createBlock();

        $section = Section::findOne(6);
        $section->block_id = $block->id;
        self::assertSame(1, $section->update(false, ['block_id']));

        self::assertTrue((bool)$block->delete());

        $section = Section::findOne(6);
        self::assertNotNull($section);
        self::assertNull($section->block_id);
    }

    public function testTheSectionCountFollowsTheSectionsThatPlaceIt(): void
    {
        $block = $this->createBlock();
        $other = $this->createBlock();

        $section = $this->createBlockSection($block);
        self::assertSame(1, Block::findOne($block->id)->section_count);

        // Moving it recounts both the block it left and the one it moved to.
        $section->block_id = $other->id;
        self::assertNotFalse($section->update(), print_r($section->getErrors(), true));

        self::assertSame(0, Block::findOne($block->id)->section_count);
        self::assertSame(1, Block::findOne($other->id)->section_count);

        self::assertTrue((bool)$section->delete());
        self::assertSame(0, Block::findOne($other->id)->section_count);
    }

    public function testASectionWhoseTypeAllowsNoBlockIsRefused(): void
    {
        $block = $this->createBlock();

        $section = Section::instantiateByType(Section::TYPE_DEFAULT);
        $section->populateEntryRelation(TestEntry::findOne(1));
        $section->block_id = $block->id;

        self::assertFalse($section->insert());
        self::assertArrayHasKey('block_id', $section->getErrors());
    }

    public function testABatchDeleteRecountsTheBlocksOnce(): void
    {
        $block = $this->createBlock();
        $section = $this->createBlockSection($block);

        self::assertSame(1, Block::findOne($block->id)->section_count);

        DeleteSections::create([Section::findOne($section->id)]);

        self::assertSame(0, Block::findOne($block->id)->section_count);
    }

    private function createBlockSection(Block $block): Section
    {
        $section = Section::instantiateByType(self::TYPE_BLOCK);
        $section->populateEntryRelation(TestEntry::findOne(1));
        $section->block_id = $block->id;

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        return $section;
    }

    public function testABlockHasNoPosition(): void
    {
        $block = $this->createBlock();

        self::assertNotContains('position', $block->attributes());
    }

    public function testDeletingABlockDeletesItsEntryRelations(): void
    {
        $block = $this->createBlock();

        $blockEntry = BlockEntry::create();
        $blockEntry->populateModelRelation($block);
        $blockEntry->populateEntryRelation(TestEntry::findOne(1));

        self::assertTrue($blockEntry->insert(), print_r($blockEntry->getErrors(), true));
        self::assertSame(1, Block::findOne($block->id)->entry_count);

        self::assertTrue((bool)$block->delete());
        self::assertNull(EntryRelation::findOne($blockEntry->id));
    }

    public function testTheEntryRelationOfABlockIsScopedToIt(): void
    {
        $block = $this->createBlock();

        $blockEntry = BlockEntry::create();
        $blockEntry->populateModelRelation($block);
        $blockEntry->populateEntryRelation(TestEntry::findOne(1));
        $blockEntry->insert();

        // The base query is unscoped and returns both kinds; a subclass query only its own.
        self::assertCount(1, BlockEntry::find()->all());
        self::assertGreaterThan(1, count(EntryRelation::find()->all()));
    }

    private function createBlock(): Block
    {
        $block = Block::create();
        $block->name = 'Test Block';

        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        return $block;
    }
}
