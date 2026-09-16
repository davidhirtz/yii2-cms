<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Models\EntryRelation;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

class BlockTest extends TestCase
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
