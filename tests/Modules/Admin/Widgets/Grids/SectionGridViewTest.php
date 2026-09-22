<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionGridView;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
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
