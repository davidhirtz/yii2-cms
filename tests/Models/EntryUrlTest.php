<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Yii;

class EntryUrlTest extends TestCase
{
    use CmsFixtureTrait;
    use ModuleTrait;

    public function testDefaultRoutesAreRegisteredByName(): void
    {
        $urlManager = Yii::$app->getUrlManager();

        self::assertTrue($urlManager->hasRoute(Entry::ROUTE_VIEW));
        self::assertTrue($urlManager->hasRoute(Entry::ROUTE_INDEX));
    }

    public function testIndexEntryUsesIndexRoute(): void
    {
        $entry = $this->createEntry(TestEntry::getModule()->entryIndexSlug);

        self::assertTrue($entry->isIndex());
        self::assertSame(Entry::ROUTE_INDEX, $entry->getRouteName());
        self::assertSame([], $entry->getRouteParams());
    }

    /**
     * {@see Entry::hasRoute()} requires sections or child entries, so an entry on its own has no URL.
     */
    public function testEntryWithoutSectionsHasNoUrl(): void
    {
        $entry = $this->createEntry();

        self::assertFalse($entry->hasRoute());
        self::assertNull($entry->getRouteName());
        self::assertFalse($entry->getUrl());
        self::assertFalse($entry->getDraftUrl());
    }

    public function testEntryUsesViewRoute(): void
    {
        $entry = $this->createEntryWithSection();

        self::assertSame(Entry::ROUTE_VIEW, $entry->getRouteName());
        self::assertSame(['slug' => $entry->getFormattedSlug()], $entry->getRouteParams());
        self::assertSame("/{$entry->getFormattedSlug()}", $entry->getUrl());
    }

    /**
     * The named route and the legacy array route must resolve to the same URL, which is what makes the
     * fallback in {@see \Hirtz\Cms\Models\ActiveRecord::getUrl()} safe.
     */
    public function testNamedRouteMatchesLegacyArrayRoute(): void
    {
        $entry = $this->createEntryWithSection();
        $urlManager = Yii::$app->getUrlManager();

        self::assertSame($urlManager->createUrl($entry->getRoute()), $entry->getUrl());
        self::assertSame($urlManager->createAbsoluteUrl($entry->getRoute()), $entry->getUrl(true));
        self::assertSame($urlManager->createDraftUrl($entry->getRoute()), $entry->getDraftUrl());
    }

    public function testSectionAppendsAnchorToEntryRoute(): void
    {
        $entry = $this->createEntry();
        $section = $this->createSection($entry);
        $entry->refresh();

        $params = $section->getRouteParams();

        self::assertSame($entry->getRouteName(), $section->getRouteName());
        self::assertSame($entry->getFormattedSlug(), $params['slug']);
        self::assertSame($section->getHtmlId(), $params['#']);
        self::assertSame(
            Yii::$app->getUrlManager()->createUrl($section->getRoute()),
            $section->getUrl()
        );
    }

    protected function createEntry(?string $slug = null): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = 'Test entry';
        $entry->slug = $slug ?? 'test-entry';

        self::assertTrue($entry->save());

        return $entry;
    }

    protected function createEntryWithSection(): TestEntry
    {
        $entry = $this->createEntry();
        $this->createSection($entry);
        $entry->refresh();

        self::assertTrue($entry->hasRoute());

        return $entry;
    }

    protected function createSection(TestEntry $entry): TestSection
    {
        $section = TestSection::create();
        $section->entry_id = $entry->id;
        $section->name = 'Test section';
        $section->slug = 'test-section';

        self::assertTrue($section->save());

        return $section;
    }
}
