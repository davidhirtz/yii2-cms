<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionGridView;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Override;
use Stringable;
use Yii;

class SectionGridViewTest extends TestCase
{
    use CmsFixtureTrait;

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

    private function render(Section $section, int $maxThumbnailCount = 3): string
    {
        $provider = Yii::$container->get(SectionActiveDataProvider::class, [], [
            'entry' => $section->entry,
            'query' => Section::find()->where(['id' => $section->id]),
        ]);

        $grid = SectionGridView::make()->provider($provider);
        $grid->maxThumbnailCount = $maxThumbnailCount;

        $html = $grid->render();

        self::assertStringContainsString('<div class="img-thumbnails">', $html);

        return $html;
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
