<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Override;
use Yii;

/**
 * Whatever an entry takes with it goes in batch: renumbering and recounting a section about to be deleted is a
 * query per asset, for nothing.
 */
class EntryDeleteQueryCountTest extends TestCase
{
    use CmsFixtureTrait;

    private const int TYPE_BLOCK = 2;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Section::getModule()->enableBlocks = true;

        Yii::$container->set(Section::class, [
            'types' => fn (): array => [
                SectionType::make(Section::TYPE_DEFAULT)->name('Default'),
                SectionType::make(self::TYPE_BLOCK)->name('Block')->allowBlock(),
            ],
        ]);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        parent::tearDown();
    }

    public function testNothingTheEntryTakesWithItIsRecounted(): void
    {
        $block = Block::create();
        $block->loadDefaultValues();
        $block->name = 'Block';
        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        $entry = TestEntry::create();
        $entry->loadDefaultValues();
        $entry->status = TestEntry::STATUS_ENABLED;
        $entry->type = TestEntry::TYPE_PAGE;
        $entry->name = 'Deleted';
        $entry->slug = 'deleted';
        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        $this->createAsset(EntryAsset::create(), $entry, 1);

        foreach ([1, 2, 3] as $ignored) {
            $section = Section::instantiateByType(self::TYPE_BLOCK);
            $section->loadDefaultValues();
            $section->status = Section::STATUS_ENABLED;
            $section->populateEntryRelation($entry);
            $section->populateBlockRelation($block);
            self::assertTrue($section->insert(), print_r($section->getErrors(), true));

            $this->createAsset(SectionAsset::create(), $section, 1);
            $this->createAsset(SectionAsset::create(), $section, 2);
        }

        $block->refresh();
        self::assertSame(3, $block->section_count);

        $entry = TestEntry::findOne($entry->id);
        self::assertNotNull($entry);

        $queries = $this->countQueries(
            fn () => self::assertSame(1, $entry->delete()),
            '/^UPDATE `(entry|section)`/'
        );

        self::assertSame(0, $queries);

        $block->refresh();
        self::assertSame(0, $block->section_count);
        self::assertSame(0, (int)Asset::find()->where(['model_id' => $entry->id])->count());
    }

    private function createAsset(Asset $asset, AssetModelInterface $model, int $fileId): void
    {
        $asset->populateModelRelation($model);
        $asset->file_id = $fileId;

        self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));
    }
}
