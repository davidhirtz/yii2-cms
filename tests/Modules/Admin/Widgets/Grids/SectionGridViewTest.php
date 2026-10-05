<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionGridView;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\File;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\Thumbnail;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\User;
use Override;
use Stringable;
use Yii;

class SectionGridViewTest extends TestCase
{
    use CmsFixtureTrait;

    private const int TYPE_BLOCK = 2;

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        Yii::$container->clear(Thumbnail::class);
        parent::tearDown();
    }

    /**
     * The fixture section holds four images.
     */
    public function testASectionWithoutANameIsPreviewedByItsImages(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->name = null;
        self::assertSame(1, $section->update());

        self::assertSame(3, substr_count($this->render($section), 'class="img-thumbnail"'));
        self::assertSame(1, substr_count($this->render($section, 1), 'class="img-thumbnail"'));
    }

    /**
     * The thumbnail the container resolves decides what has a preview, not the file (media-video draws one for a
     * video).
     */
    public function testTheThumbnailDecidesWhichFilesHaveAPreview(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->name = null;
        self::assertSame(1, $section->update());

        $fileIds = array_map(fn (Asset $asset): int => $asset->file_id, $section->getVisibleAssets());
        File::updateAll(['extension' => 'mp4'], ['id' => $fileIds]);

        Yii::$container->set(Thumbnail::class, []);
        self::assertStringNotContainsString('img-thumbnail', $this->renderSection($section));

        Yii::$container->set(Thumbnail::class, VideoThumbnail::class);
        self::assertSame(3, substr_count($this->render($section), 'class="img-thumbnail video"'));
    }

    /**
     * The grid is not paginated, so a query per row would grow with the entry.
     */
    public function testTheThumbnailsAreLoadedWithTheSections(): void
    {
        $module = Section::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;

        Yii::$container->set(Section::class, [
            'types' => fn (): array => [
                SectionType::make(Section::TYPE_DEFAULT)->name('Default'),
                SectionType::make(self::TYPE_BLOCK)->name('Block')->allowBlock(),
            ],
        ]);

        $this->getWebUser()->setIdentity(User::findOne(['name' => 'owner']));

        $entry = TestEntry::create();
        $entry->loadDefaultValues();
        $entry->status = TestEntry::STATUS_ENABLED;
        $entry->type = TestEntry::TYPE_PAGE;
        $entry->name = 'Page';
        $entry->slug = 'page';
        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        $block = Block::create();
        $block->loadDefaultValues();
        $block->name = 'Block';
        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        $this->createAsset(BlockAsset::create(), $block);

        $this->createThumbnailSections($entry, $block);
        $render = fn () => $this->renderEntry($entry);
        $render();

        $queries = $this->countQueries($render);

        $this->createThumbnailSections($entry, $block);
        $this->createThumbnailSections($entry, $block);

        self::assertSame(6, substr_count($this->renderEntry($entry), 'class="img-thumbnail"'));
        self::assertSame($queries, $this->countQueries($render));
    }

    public function testPlainTextContentIsEncoded(): void
    {
        $section = new PlainContentSection();
        $section->content = 'AT&T <b>bold</b>';

        self::assertSame('AT&amp;T &lt;b&gt;bold&lt;/b&gt;', $this->renderNameColumnContent($section));
    }

    public function testHtmlContentIsReducedToItsText(): void
    {
        $section = new HtmlContentSection();
        $section->content = '<p>AT&amp;T <b>bold</b></p>';

        self::assertSame('AT&amp;T bold', $this->renderNameColumnContent($section));
    }

    private function renderNameColumnContent(Section $section): string
    {
        $html = (string)(new NameColumnSectionGridView())->renderNameColumnContent($section);
        return preg_match('#<a[^>]*>(.*)</a>#s', $html, $matches) ? $matches[1] : self::fail($html);
    }

    /**
     * One section previewed by its own image, one by its block's.
     */
    private function createThumbnailSections(Entry $entry, Block $block): void
    {
        foreach ([Section::TYPE_DEFAULT, self::TYPE_BLOCK] as $type) {
            $section = Section::instantiateByType($type);
            $section->loadDefaultValues();
            $section->status = Section::STATUS_ENABLED;
            $section->populateEntryRelation($entry);

            if ($type === self::TYPE_BLOCK) {
                $section->populateBlockRelation($block);
            }

            self::assertTrue($section->insert(), print_r($section->getErrors(), true));

            if ($type === Section::TYPE_DEFAULT) {
                $this->createAsset(SectionAsset::create(), $section);
            }
        }
    }

    private function renderEntry(Entry $entry): string
    {
        Yii::$app->getView()->clear();

        $html = Yii::$app->runAction('admin/cms/section/index', ['entry' => $entry->id]);
        self::assertIsString($html);

        return $html;
    }

    private function createAsset(Asset $asset, AssetModelInterface $model): void
    {
        $asset->populateModelRelation($model);
        $asset->file_id = 1;

        self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));
    }

    private function render(Section $section, int $maxThumbnailCount = 3): string
    {
        $html = $this->renderSection($section, $maxThumbnailCount);
        self::assertStringContainsString('<div class="img-thumbnails">', $html);

        return $html;
    }

    private function renderSection(Section $section, int $maxThumbnailCount = 3): string
    {
        $provider = Yii::$container->get(SectionActiveDataProvider::class, [], [
            'entry' => $section->entry,
            'query' => Section::find()->where(['id' => $section->id]),
        ]);

        $grid = SectionGridView::make()->provider($provider);
        $grid->maxThumbnailCount = $maxThumbnailCount;

        return $grid->render();
    }
}

class VideoThumbnail extends Thumbnail
{
    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Div::make()->class('img-thumbnail video');
    }
}

class PlainContentSection extends Section
{
    #[Override]
    protected function getDefaultCustomAttributes(): array
    {
        return [TextCustomAttribute::make('content')];
    }
}

class HtmlContentSection extends Section
{
    #[Override]
    protected function getDefaultCustomAttributes(): array
    {
        return [HtmlCustomAttribute::make('content')];
    }
}

class NameColumnSectionGridView extends SectionGridView
{
    public function renderNameColumnContent(Section $section): Stringable|string
    {
        return $this->getNameColumnContent($section);
    }
}
