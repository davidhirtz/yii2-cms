<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Closure;
use Hirtz\Cms\Models\ActiveRecord;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\Types\BlockSectionType;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\SectionStack;
use Hirtz\Skeleton\Models\CustomAttributes\BooleanCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Override;
use Yii;

class BlockSectionFieldsTest extends TestCase
{
    private int $sectionId = 0;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::setAlias('@cmsTestViews', dirname(__DIR__) . '/data/views');

        $module = Section::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;

        Yii::$container->set(Section::class, ['class' => FieldsSection::class]);
        Yii::$container->set(SectionAsset::class, ['class' => FieldsSectionAsset::class]);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        Yii::$container->clear(SectionAsset::class);

        parent::tearDown();
    }

    public function testABlockCarriesTheFieldsOfTheSectionButItsSlugAndName(): void
    {
        $block = $this->createBlock(FieldsSection::TYPE_COLUMN);

        self::assertInstanceOf(GroupCustomAttribute::class, $block->getCustomAttribute('buttons'));
        self::assertNotNull($block->getCustomAttribute('content'));
        self::assertNull($block->getCustomAttribute('slug'));
        self::assertNull($block->getCustomAttribute('name'));
    }

    public function testTheClosuresOfTheSectionFieldsReceiveTheBlock(): void
    {
        $block = $this->createBlock(FieldsSection::TYPE_COLUMN);
        $section = $this->createSection(FieldsSection::TYPE_COLUMN);

        self::assertTrue($block->getCustomAttribute('autoplay')?->isVisible($block));
        self::assertFalse($section->getCustomAttribute('autoplay')?->isVisible($section));
    }

    public function testABlockSavesTheFieldsOfTheSection(): void
    {
        $block = $this->createBlock(FieldsSection::TYPE_COLUMN);
        $block->name = 'Å ą Ж 漢 🙂';
        $block->setAttribute('buttons', [['label' => "Mu\u{308}ller"]]);

        self::assertTrue($block->insert(), print_r($block->getErrors(), true));
        self::assertSame([['label' => "Mu\u{308}ller"]], Block::findOne($block->id)?->getAttribute('buttons'));
    }

    public function testABlockKeepsItsRequiredNameUnderATypeHidingASectionsName(): void
    {
        $block = $this->createBlock(FieldsSection::TYPE_COLUMN);

        self::assertFalse($this->createSection(FieldsSection::TYPE_COLUMN)->isAttributeVisible('name'));
        self::assertTrue($block->isAttributeVisible('name'));
        self::assertFalse($block->insert());
        self::assertArrayHasKey('name', $block->getErrors());
    }

    public function testABlockAssetCarriesTheFieldsOfTheSectionAsset(): void
    {
        $asset = Yii::createObject(BlockAsset::class);

        self::assertNotNull($asset->getCustomAttribute('badge'));
        self::assertNotNull($asset->getCustomAttribute('alt_text'));
    }

    public function testABlockAssetTranslatesTheFieldsTheSectionAssetTranslates(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$container->set(SectionAsset::class, ['class' => FieldsSectionAsset::class, 'translatableAttributes' => ['alt_text']]);

        $asset = Yii::createObject(BlockAsset::class);

        self::assertTrue($asset->getCustomAttribute('alt_text')?->isTranslatable());
        self::assertFalse($asset->getCustomAttribute('content')?->isTranslatable());
    }

    public function testABlockAssetConfiguredItselfKeepsItsOwnTranslatedFields(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$container->set(SectionAsset::class, ['class' => FieldsSectionAsset::class, 'translatableAttributes' => ['alt_text']]);
        Yii::$container->set(BlockAsset::class, ['translatableAttributes' => ['content']]);

        try {
            $asset = Yii::createObject(BlockAsset::class);

            self::assertTrue($asset->getCustomAttribute('content')?->isTranslatable());
            self::assertFalse($asset->getCustomAttribute('alt_text')?->isTranslatable());
        } finally {
            Yii::$container->clear(BlockAsset::class);
        }
    }

    public function testABlockSectionShowsOnlyTheStatusTheTypeAndTheBlock(): void
    {
        $section = $this->createSection(FieldsSection::TYPE_BLOCK);

        foreach (['name', 'content', 'slug', 'buttons', 'autoplay'] as $name) {
            self::assertFalse($section->isAttributeVisible($name), $name);
        }

        foreach (['status', 'type', 'block_id'] as $name) {
            self::assertTrue($section->isAttributeVisible($name), $name);
        }

        self::assertNotContains('buttons', $section->safeAttributes());
    }

    public function testVisibleFieldsAddToTheFieldsShown(): void
    {
        $type = BlockSectionType::make(Section::TYPE_BLOCK)->visibleFields('status', 'type', 'block_id', 'slug');

        self::assertNotContains('slug', $type->getHiddenFields());
        self::assertContains('buttons', $type->getHiddenFields());
    }

    public function testABlockSectionTakesTheGroupOfItsBlocksType(): void
    {
        $group = FieldsSection::findBlockSectionType()?->getGroup();
        self::assertInstanceOf(Closure::class, $group);

        $section = $this->createSection(FieldsSection::TYPE_BLOCK);
        self::assertNull($group($section));

        $section->populateBlockRelation($this->createBlock(FieldsSection::TYPE_COLUMN));
        self::assertSame('column', $group($section));

        $section->populateBlockRelation($this->createBlock(FieldsSection::TYPE_GALLERY));
        self::assertSame('@cmsTestViews/site/_alt', $group($section));
    }

    public function testABlockSectionSharesTheRowOfTheSectionsOfItsBlocksType(): void
    {
        $block = $this->createSection(FieldsSection::TYPE_BLOCK);
        $block->populateBlockRelation($this->createBlock(FieldsSection::TYPE_COLUMN));

        $stack = (string)SectionStack::make()
            ->viewFile('@cmsTestViews/site/_sections')
            ->sections([$this->createSection(FieldsSection::TYPE_COLUMN), $block]);

        self::assertSame(1, substr_count($stack, '<g '));
        self::assertStringContainsString('key="column"', $stack);
    }

    public function testTheContentModelIsTheVisibleBlock(): void
    {
        $block = $this->createBlock(FieldsSection::TYPE_COLUMN);

        $section = $this->createSection(FieldsSection::TYPE_BLOCK);
        self::assertSame($section, $section->getContentModel());

        $section->populateBlockRelation($block);
        self::assertSame($block, $section->getContentModel());

        $column = $this->createSection(FieldsSection::TYPE_COLUMN);
        $column->populateBlockRelation($block);
        self::assertSame($column, $column->getContentModel());
    }

    private function createBlock(int $type): Block
    {
        $block = Block::create();
        $block->type = $type;

        return $block;
    }

    private function createSection(int $type): Section
    {
        $section = Section::instantiateByType($type);
        $section->id = ++$this->sectionId;

        return $section;
    }
}

/**
 * Declared here, since a second class in a test file is only autoloadable while that file runs.
 *
 * @property list<array<string, mixed>>|null $buttons
 */
class FieldsSection extends Section
{
    public const int TYPE_COLUMN = 1;
    public const int TYPE_GALLERY = 2;
    public const int TYPE_BLOCK = 3;

    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            ...parent::getCustomAttributes(),
            GroupCustomAttribute::make('buttons')
                ->multiple()
                ->attributes([
                    TextCustomAttribute::make('label')
                        ->required(),
                ]),
            BooleanCustomAttribute::make('autoplay')
                ->visible(static fn (ActiveRecord $model): bool => $model instanceof Block),
        ];
    }

    #[Override]
    public function getTypes(): array
    {
        return [
            SectionType::make(self::TYPE_COLUMN)
                ->name('Column')
                ->hiddenFields('name')
                ->group('column'),
            SectionType::make(self::TYPE_GALLERY)
                ->name('Gallery')
                ->viewFile('@cmsTestViews/site/_alt')
                ->group(static fn (Section $section): string => 'gallery-' . $section->id),
            BlockSectionType::make(self::TYPE_BLOCK),
        ];
    }
}

class FieldsSectionAsset extends SectionAsset
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            ...parent::getCustomAttributes(),
            TextCustomAttribute::make('badge'),
        ];
    }
}
