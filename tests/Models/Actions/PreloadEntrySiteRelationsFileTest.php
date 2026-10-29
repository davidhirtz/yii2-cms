<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\PreloadEntrySiteRelations;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\BlockSectionType;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\CustomAttributes\FileCustomAttribute;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Override;
use Yii;

/**
 * The files the custom attributes name load with the asset files, in one query.
 */
class PreloadEntrySiteRelationsFileTest extends TestCase
{
    use CmsFixtureTrait;
    use ModuleTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::getModule()->enableBlocks = true;
        self::getModule()->enableSectionEntries = true;
        ActiveQuery::setStatus(TestEntry::STATUS_DRAFT);

        $hero = [FileCustomAttribute::make('hero')];

        $sectionAttributes = [
            ...$hero,
            GroupCustomAttribute::make('gallery')
                ->multiple()
                ->attributes([FileCustomAttribute::make('image'), TextCustomAttribute::make('caption')]),
        ];

        Yii::$container->set(Section::class, ['class' => FileSectionModel::class, 'customAttributes' => $sectionAttributes]);
        Yii::$container->set(FileSectionModel::class, ['customAttributes' => $sectionAttributes]);

        foreach ([Entry::class, TestEntry::class, Block::class, EntryAsset::class] as $class) {
            Yii::$container->set($class, ['customAttributes' => $hero]);
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach ([Section::class, FileSectionModel::class, Entry::class, TestEntry::class, Block::class, EntryAsset::class] as $class) {
            Yii::$container->clear($class);
        }

        parent::tearDown();
    }

    public function testEveryFileIsLoadedInTheAssetFilesQuery(): void
    {
        $file = fn (string $key): int => (int)$this->getFileFixtureData($key)['id'];

        $block = Block::create();
        $block->name = 'Ą Block';
        $block->setAttribute('hero', $file('file-2'));
        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        $entry = $this->getEntryFromFixture('page-enabled');

        $placing = FileSectionModel::instantiateByType(FileSectionModel::TYPE_BLOCK);
        $placing->populateEntryRelation($entry);
        $placing->populateBlockRelation($block);
        self::assertTrue($placing->insert(), print_r($placing->getErrors(), true));

        $this->storeHero(TestEntry::findOne($entry->id), ['hero' => $file('file-3')]);
        // the blog section links entry 6, under a type this section model knows
        Section::findOne($this->getSectionFixtureData('section-blog-draft')['id'])?->updateAttributes(['type' => Section::TYPE_DEFAULT]);
        $this->storeHero(TestEntry::findOne(6), ['hero' => $file('file-6')]);
        $this->storeHero(EntryAsset::findOne($this->getAssetFixtureData('entry-asset')['id']), ['hero' => $file('file-5')]);
        $this->storeHero(Section::findOne($this->getSectionFixtureData('section-column')['id']), [
            'hero' => $file('file-4'),
            'gallery' => [['image' => $file('file-5'), 'caption' => 'Москва'], ['image' => $file('file-6')]],
        ]);

        $entry = $this->getEntryFromFixture('page-enabled');
        $preload = null;

        $queries = $this->countQueries(function () use ($entry, &$preload): void {
            $preload = new PreloadEntrySiteRelations(['entry' => $entry]);
        }, '/FROM `file`/');

        self::assertSame(1, $queries, 'the files of the assets and of the custom attributes share one query');
        self::assertInstanceOf(PreloadEntrySiteRelations::class, $preload);

        $queries = $this->countQueries(function () use ($entry, $preload, $file): void {
            self::assertSame($file('file-3'), FileCustomAttribute::getFile($entry, 'hero')?->id);
            self::assertSame($file('file-6'), FileCustomAttribute::getFile($preload->entries[6], 'hero')?->id);

            $sections = $entry->sections;
            $column = $sections[$this->getSectionFixtureData('section-column')['id']];
            $items = array_values($column->getCustomAttributeItems('gallery'));

            self::assertSame($file('file-4'), FileCustomAttribute::getFile($column, 'hero')?->id);
            self::assertSame($file('file-5'), FileCustomAttribute::getFile($items[0], 'image')?->id);
            self::assertSame($file('file-6'), FileCustomAttribute::getFile($items[1], 'image')?->id);

            $placing = array_values(array_filter($sections, fn (Section $section): bool => (bool)$section->block_id))[0];
            self::assertSame($file('file-2'), FileCustomAttribute::getFile($placing->block ?? $placing, 'hero')?->id);

            $assetId = $this->getAssetFixtureData('entry-asset')['id'];
            $asset = array_values(array_filter($preload->assets, fn ($asset): bool => (int)$asset->id === (int)$assetId))[0] ?? null;
            self::assertNotNull($asset);
            self::assertSame($file('file-5'), FileCustomAttribute::getFile($asset, 'hero')?->id);
        });

        self::assertSame(0, $queries);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function storeHero(?ActiveRecord $record, array $values): void
    {
        self::assertNotNull($record);
        $stored = $record->getAttribute('custom_attributes');
        $record->updateAttributes(['custom_attributes' => [...is_array($stored) ? $stored : [], ...$values]]);
    }
}

/**
 * Declared here, since a second class in a test file is only autoloadable while that file runs.
 */
class FileSectionModel extends Section
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
