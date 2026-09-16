<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;

/**
 * A type narrows what the installation turned on, and the model is the only thing a caller has to ask.
 */
class TypeCapabilityTest extends TestCase
{
    use CmsFixtureTrait;
    use ModuleTrait;

    public function testTheTypeTakesTheAssetsOffAnEntry(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        self::assertTrue(self::getModule()->enableEntryAssets);

        $entry->type = TestEntry::TYPE_PAGE;
        self::assertTrue($entry->allowsAssets());

        $entry->type = TestEntry::TYPE_POST;
        self::assertFalse($entry->allowsAssets());
        self::assertSame([], $entry->getVisibleAssets());
    }

    public function testTheModuleStillDecidesFirst(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $entry->type = TestEntry::TYPE_PAGE;

        $module = self::getModule();
        $module->enableEntryAssets = false;

        try {
            self::assertFalse($entry->allowsAssets(), 'A type cannot turn on what the installation turned off.');
        } finally {
            $module->enableEntryAssets = true;
        }
    }

    public function testTheTypeTakesTheEntriesOffASection(): void
    {
        $section = $this->getSectionFromFixture('section-headline');

        $module = self::getModule();
        $module->enableSectionEntries = true;

        try {
            $section->type = TestSection::TYPE_BLOG;
            self::assertTrue($section->allowsEntries());

            $section->type = TestSection::TYPE_GALLERY;
            self::assertFalse($section->allowsEntries());
        } finally {
            $module->enableSectionEntries = false;
        }
    }

    public function testASectionTypeTakesItsOwnAssets(): void
    {
        $section = $this->getSectionFromFixture('section-headline');

        $section->type = TestSection::TYPE_HEADLINE;
        self::assertTrue($section->allowsAssets());

        $section->type = TestSection::TYPE_BLOG;
        self::assertFalse($section->allowsAssets());
        self::assertSame([], $section->getVisibleAssets());
    }
}
