<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;

class BlockSubmenuTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = TestEntry::getModule();
        $module->enableBlocks = true;
        $module->enableBlockEntries = true;
    }

    /**
     * `fa-chain` is an alias of `fa-link` and draws the same glyph, so the two tabs were indistinguishable
     * (monorepo issue #148).
     */
    public function testTheSectionsAndEntriesTabsCarryDifferentIcons(): void
    {
        $block = Block::create();
        $block->name = 'Block';
        self::assertTrue($block->insert());

        $block->section_count = 1;

        $html = BlockSubmenu::make()
            ->model($block)
            ->render();

        self::assertStringContainsString('fa-th-list', $html);
        self::assertStringContainsString('fa-chain', $html);
        self::assertStringNotContainsString('fa-link', $html);
    }

    public function testTheLinkedEntriesFollowTheAssets(): void
    {
        // The module cascaded it off at `init()`, before `setUp()` turned the blocks on.
        TestEntry::getModule()->enableBlockAssets = true;

        $block = Block::create();
        $block->name = 'Block';
        self::assertTrue($block->insert());

        $html = BlockSubmenu::make()
            ->model($block)
            ->render();

        $assets = strpos($html, '/admin/cms/block-asset/index');
        $entries = strpos($html, '/admin/cms/block-entry/index');

        self::assertNotFalse($assets);
        self::assertNotFalse($entries);
        self::assertLessThan($entries, $assets);
        self::assertStringContainsString('>Linked entries</span>', $html);
    }
}
