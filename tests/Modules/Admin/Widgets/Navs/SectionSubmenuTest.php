<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;

class SectionSubmenuTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheBackButtonLeadsToTheSectionsOfTheEntry(): void
    {
        $section = $this->getSectionFromFixture('section-headline');

        $html = SectionSubmenu::make()
            ->model($section)
            ->render();

        self::assertStringContainsString('<a class="nav-link nav-back-link" href="/admin/cms/section/index?entry=' . $section->entry_id . '"', $html);
    }

    public function testTheBackButtonCanBeTurnedOff(): void
    {
        $html = SectionSubmenu::make()
            ->model($this->getSectionFromFixture('section-headline'))
            ->backUrl(false)
            ->render();

        self::assertStringNotContainsString('nav-back-link', $html);
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
