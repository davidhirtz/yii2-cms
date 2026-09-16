<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Builders;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Models\Builders\EntrySiteRelationsBuilder;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\BlockSectionType;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\ActiveQuery;
use Override;
use Yii;

class EntrySiteRelationsBuilderBlockTest extends TestCase
{
    use CmsFixtureTrait;
    use ModuleTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = self::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;
        $module->enableBlockEntries = true;

        Yii::$container->set(Section::class, BlockSectionModel::class);
        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        parent::tearDown();
    }

    public function testTheBlockOfASectionIsLoadedWithItsAssetsAndEntries(): void
    {
        $block = Block::create();
        $block->name = 'Loaded Block';
        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        $asset = BlockAsset::create();
        $asset->populateModelRelation($block);
        $asset->file_id = $this->getFileFixtureData('file-1')['id'];
        self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));

        $blockEntry = BlockEntry::create();
        $blockEntry->populateModelRelation($block);
        $blockEntry->populateEntryRelation($this->getEntryFromFixture('post-1'));
        self::assertTrue($blockEntry->insert(), print_r($blockEntry->getErrors(), true));

        $entry = $this->getEntryFromFixture('page-enabled');

        $section = BlockSectionModel::instantiateByType(BlockSectionModel::TYPE_BLOCK);
        $section->populateEntryRelation($entry);
        $section->populateBlockRelation(Block::findOne($block->id));
        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $entry = $this->getEntryFromFixture('page-enabled');
        new EntrySiteRelationsBuilder(['entry' => $entry]);

        $sections = array_filter($entry->sections, fn (Section $section): bool => (bool)$section->block_id);
        $section = reset($sections);

        self::assertNotFalse($section);
        self::assertTrue($section->isRelationPopulated('block'));
        self::assertSame('Loaded Block', $section->block?->name);

        // The section delegates, so a project view reading the section gets the block's assets and entries.
        self::assertCount(1, $section->getVisibleAssets());
        self::assertCount(1, $section->getVisibleEntries());
        self::assertTrue($section->block->isRelationPopulated('assets'));
    }
}

/**
 * A scratch section model, declared here because a second class in a test file is only autoloadable while that
 * file runs.
 */
class BlockSectionModel extends Section
{
    public const int TYPE_BLOCK = 2;

    #[Override]
    public function getTypes(): array
    {
        return [
            SectionType::make(self::TYPE_DEFAULT)
                ->name('Default'),
            BlockSectionType::make(self::TYPE_BLOCK),
        ];
    }
}
