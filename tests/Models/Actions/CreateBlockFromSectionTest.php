<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Closure;
use Hirtz\Cms\Models\Actions\CreateBlockFromSection;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Models\Types\BlockSectionType;
use Hirtz\Cms\Models\Types\BlockType;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Override;
use Yii;

class CreateBlockFromSectionTest extends TestCase
{
    use CmsFixtureTrait;

    private const int TYPE_HEADLINE = 1;
    private const int TYPE_COLUMN = 2;
    private const int TYPE_BLOG = 4;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;
        $module->enableBlockEntries = true;

        $this->setSectionTypes();
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        Yii::$container->clear(Block::class);
        DefinitionRegistry::reset();

        parent::tearDown();
    }

    public function testTheBlockSectionTypeIsAddedWhileBlocksAreEnabled(): void
    {
        self::assertInstanceOf(BlockSectionType::class, Section::findType(Section::TYPE_BLOCK));
        self::assertSame(Section::findType(Section::TYPE_BLOCK), Section::findBlockSectionType());

        TestEntry::getModule()->enableBlocks = false;
        DefinitionRegistry::reset();

        self::assertNull(Section::findBlockSectionType());
    }

    public function testADeclaredBlockSectionTypeReplacesTheAddedOne(): void
    {
        $this->setSectionTypes(BlockSectionType::make(7)->name('Placed block'));

        self::assertNull(Section::findType(Section::TYPE_BLOCK));
        self::assertSame(7, Section::findBlockSectionType()?->value);
    }

    public function testTheSharedSectionTypesReplaceTheDefaultBlockType(): void
    {
        self::assertSame([self::TYPE_HEADLINE, self::TYPE_BLOG], array_keys(Block::getTypeDefinitions()));

        TestEntry::getModule()->enableBlocks = false;
        DefinitionRegistry::reset();

        self::assertSame([Block::TYPE_DEFAULT], array_keys(Block::getTypeDefinitions()));
    }

    public function testTheBlockTypesAreMergedWithTheSharedSectionTypes(): void
    {
        Yii::$container->set(Block::class, [
            'types' => fn (): array => [BlockType::make(8)->name('Teaser')],
        ]);

        self::assertSame([8, self::TYPE_HEADLINE, self::TYPE_BLOG], array_keys(Block::getTypeDefinitions()));
        self::assertSame(Section::findType(self::TYPE_HEADLINE), Block::findType(self::TYPE_HEADLINE));
    }

    public function testABlockTypeWinsOverASharedSectionTypeOfItsValue(): void
    {
        $teaser = BlockType::make(self::TYPE_HEADLINE)->name('Teaser');

        Yii::$container->set(Block::class, [
            'types' => fn (): array => [$teaser],
        ]);

        self::assertSame([self::TYPE_HEADLINE, self::TYPE_BLOG], array_keys(Block::getTypeDefinitions()));
        self::assertSame('Teaser', Block::findType(self::TYPE_HEADLINE)?->getName());
    }

    public function testATypeCarryingABlockOrAModelClassIsNotShared(): void
    {
        self::assertFalse(SectionType::make(9)->allowBlock()->isSharedWithBlocks());
        self::assertFalse(SectionType::make(9)->modelClass(Section::class)->isSharedWithBlocks());
        self::assertFalse(BlockSectionType::make(9)->isSharedWithBlocks());
        self::assertTrue(SectionType::make(9)->isSharedWithBlocks());
    }

    public function testTheSectionBecomesTheBlockSectionOfANewBlock(): void
    {
        $section = Section::findOne(1);
        $section->setAttribute('subtitle', 'Å 🙂 Mu\u{308}ller');
        self::assertNotFalse($section->update(), print_r($section->getErrors(), true));

        $action = CreateBlockFromSection::create(Section::findOne(1));

        self::assertSame([], $action->getFirstErrors());

        $block = Block::findOne($action->block->id);
        self::assertSame(self::TYPE_HEADLINE, $block->type);
        self::assertSame('Test Headline', $block->name);
        self::assertSame('<p>Test Headline Content</p>', $block->getAttribute('content'));
        self::assertSame('Å 🙂 Mu\u{308}ller', $block->getAttribute('subtitle'));
        self::assertSame(1, $block->section_count);
        self::assertSame(4, $block->asset_count);
        self::assertCount(4, BlockAsset::findAll(['model_id' => $block->id]));

        $section = Section::findOne(1);
        self::assertSame(Section::TYPE_BLOCK, $section->type);
        self::assertSame($block->id, $section->block_id);
        self::assertSame(0, $section->asset_count);
        self::assertSame([], SectionAsset::findAll(['model_id' => 1]));
        self::assertSame($block->id, $section->getVisibleBlock()?->id);
    }

    public function testTheLinkedEntriesMoveInTheirOrder(): void
    {
        $action = CreateBlockFromSection::create(Section::findOne(3));

        self::assertSame([], $action->getFirstErrors());

        $relations = BlockEntry::find()
            ->where(['model_id' => $action->block->id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        self::assertSame([6, 1], array_map(fn (BlockEntry $relation): int => $relation->entry_id, $relations));
        self::assertSame(2, Block::findOne($action->block->id)->entry_count);
        self::assertSame(0, Section::findOne(3)->entry_count);
        self::assertSame([], SectionEntry::findAll(['model_id' => 3]));
    }

    public function testANamelessSectionBecomesABlockWithATranslatedName(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$container->set(Block::class, ['i18nAttributes' => ['name']]);
        Block::instance(true);

        $section = Section::instantiateByType(self::TYPE_HEADLINE);
        $section->populateEntryRelation(TestEntry::findOne(1));
        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $action = CreateBlockFromSection::create($section);

        self::assertSame([], $action->getFirstErrors());
        self::assertSame('Headline', Block::find()->withTranslations()->where(['id' => $action->block->id])->one()?->getAttribute('name_de'));
    }

    public function testASectionWithoutANameNamesTheBlockAfterItsType(): void
    {
        $section = Section::instantiateByType(self::TYPE_HEADLINE);
        $section->populateEntryRelation(TestEntry::findOne(1));
        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $action = CreateBlockFromSection::create($section);

        self::assertSame([], $action->getFirstErrors());
        self::assertSame('Headline', Block::findOne($action->block->id)->name);
    }

    public function testASectionOfATypeNotSharedIsRefused(): void
    {
        $count = Block::find()->count();
        $section = Section::findOne(2);

        self::assertFalse($section->allowsBlockConversion());
        self::assertFalse(Yii::createObject(CreateBlockFromSection::class, [$section])->run());
        self::assertArrayHasKey('type', $section->getErrors());
        self::assertSame($count, Block::find()->count());
    }

    public function testAnUnavailableBlockSectionTypeRefusesTheConversion(): void
    {
        $this->setSectionTypes(BlockSectionType::make(7)->available(false));

        self::assertFalse(Section::findOne(1)->allowsBlockConversion());
    }

    public function testAFailingBlockRollsEverythingBack(): void
    {
        $this->setSectionTypes(null, fn (): array => [
            TextCustomAttribute::make('subtitle')->required(),
        ]);

        $count = Block::find()->count();
        $action = CreateBlockFromSection::create(Section::findOne(1));

        self::assertArrayHasKey('subtitle', $action->getFirstErrors());
        self::assertSame($count, Block::find()->count());
        self::assertSame(self::TYPE_HEADLINE, Section::findOne(1)->type);
        self::assertCount(4, SectionAsset::findAll(['model_id' => 1]));
    }

    private function setSectionTypes(?SectionType $extra = null, ?Closure $customAttributes = null): void
    {
        Yii::$container->set(Section::class, [
            'types' => fn (): array => [
                SectionType::make(self::TYPE_HEADLINE)
                    ->name('Headline')
                    ->customAttributes($customAttributes ?? fn (): array => [
                        TextCustomAttribute::make('subtitle'),
                    ]),
                SectionType::make(self::TYPE_COLUMN)
                    ->name('Column')
                    ->shareWithBlocks(false),
                SectionType::make(self::TYPE_BLOG)
                    ->name('Blog'),
                ...($extra ? [$extra] : []),
            ],
        ]);

        DefinitionRegistry::reset();
    }
}
