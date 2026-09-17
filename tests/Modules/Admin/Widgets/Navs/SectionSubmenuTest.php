<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;

/**
 * A submenu holds the views of one record, so it never links out of it — the way back is the header path.
 */
class SectionSubmenuTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheSubmenuHoldsNoLinkOutOfTheSection(): void
    {
        $html = SectionSubmenu::make()
            ->model($this->getSectionFromFixture('section-headline'))
            ->render();

        self::assertStringNotContainsString('angle-double-left', $html);
        self::assertStringNotContainsString('/admin/cms/section/index', $html);
    }

    public function testTheFirstTabIsTheSectionItself(): void
    {
        $section = $this->getSectionFromFixture('section-headline');

        $html = SectionSubmenu::make()
            ->model($section)
            ->render();

        self::assertStringContainsString('/admin/cms/section/update?id=' . $section->id, $html);
        self::assertStringContainsString('>' . $section->getAdminType() . '</span>', $html);
    }
}
