<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionActiveForm;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;

/**
 * The slug field shows the entry's URL in front of the section's anchor (monorepo issue #761).
 */
class SectionActiveFormTest extends TestCase
{
    use CmsFixtureTrait;

    public function testALongBaseUrlIsShortenedAsTextWithTheFullUrlAsTitle(): void
    {
        $section = Section::create();
        $section->populateEntryRelation($this->getEntryFromFixture('page-enabled'));

        $html = SectionActiveForm::make()
            ->model($section)
            ->maxBaseUrlLength(20)
            ->render();

        self::assertStringNotContainsString('&lt;span', $html);
        self::assertStringContainsString('title="https://www.domain.localhost/test-1#"', $html);
        self::assertStringContainsString('>https://www.domain.l…#</div>', $html);
    }

    public function testAShortBaseUrlIsShownWhole(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        $section = Section::create();
        $section->populateEntryRelation($entry);

        $html = SectionActiveForm::make()
            ->model($section)
            ->maxBaseUrlLength(false)
            ->render();

        self::assertStringNotContainsString('…#', $html);
    }
}
