<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Models\Actions\PreloadEntrySiteRelations;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Types\EntryType;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\MetaTags;
use Hirtz\Media\Models\File;
use Hirtz\Media\Transformations\Transformation;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Helpers\StructuredData;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\StructuredData\Event;
use Hirtz\Skeleton\Widgets\StructuredData\Organization;
use Hirtz\Skeleton\Models\CustomAttributes\DateTimeCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Override;
use Yii;

class MetaTagsTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getUrlManager()->i18nUrl = false;

        // An entry has no content of its own; the fallback only applies where a project declares one.
        Yii::$container->set(TestEntry::class, [
            'customAttributes' => [HtmlCustomAttribute::make('content')],
        ]);

        TestEntry::instance(true);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(TestEntry::class);
        TestEntry::instance(true);

        parent::tearDown();
    }

    public function testTheDocumentTitleFallsBackToTheName(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        $this->render($entry);
        self::assertSame($entry->name, Yii::$app->getView()->title);

        $entry->title = 'A separate title';

        $this->render($entry);
        self::assertSame('A separate title', Yii::$app->getView()->title);
    }

    public function testTheDescriptionFallsBackToTheContent(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $entry->type = TestEntry::TYPE_POST;
        $entry->content = '<p>Some content</p><p>AT&amp;T</p>';

        $this->render($entry);
        self::assertStringContainsString('<meta name="description" content="Some content AT&amp;T">', $this->getHead());

        $entry->description = 'A separate description';

        $this->render($entry);
        self::assertStringContainsString('A separate description', $this->getHead());
    }

    /**
     * The page type hides `content`, so what it still holds is not the page's description.
     */
    public function testHiddenContentIsNotTheDescription(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $entry->content = 'Some content';

        $this->render($entry);
        self::assertStringNotContainsString('Some content', $this->getHead());
    }

    public function testTheOpenGraphTagsAreRegistered(): void
    {
        $this->render($this->getEntryFromFixture('page-enabled'));

        self::assertStringContainsString('og:type', $this->getHead());
    }

    public function testTheSocialTagsCanBeTurnedOff(): void
    {
        $this->render($this->getEntryFromFixture('page-enabled'), ['enableSocialMetaTags' => false]);

        self::assertStringNotContainsString('og:type', $this->getHead());
    }

    /**
     * Only the meta image is offered to the social networks, not every asset of the entry.
     */
    public function testOnlyTheMetaImageIsRegistered(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $meta = $this->getAssetFromFixture('entry-meta-image');

        $this->render($entry);
        $head = $this->getHead();

        self::assertStringContainsString($meta->file->getUrl(), $head);
        self::assertStringNotContainsString($this->getAssetFromFixture('entry-asset')->file->getFilename(), $head);
    }

    /**
     * The `og` preset fits a 1000 × 1000 JPEG into 1200 × 630, which is what the size tags say; the SVG meta image
     * cannot be transformed and is shared as it is.
     */
    public function testTheShareImageDefaultsToTheOpenGraphTransformation(): void
    {
        $this->render($this->getEntryFromFixture('page-enabled'), ['assetType' => null]);
        $head = $this->getHead();

        $url = $this->getAssetFromFixture('entry-asset')->file->getTransformationUrl(Transformation::NAME_OPEN_GRAPH);

        self::assertNotNull($url);
        self::assertStringContainsString($url, $head);
        self::assertStringContainsString('<meta property="og:image:width" content="630">', $head);
        self::assertStringContainsString($this->getAssetFromFixture('entry-meta-image')->file->getUrl(), $head);
    }

    public function testTheShareImageTakesATransformationItsNameOrNone(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $file = $this->getAssetFromFixture('entry-asset')->file;
        $url = $file->getTransformationUrl(Transformation::NAME_ADMIN);

        self::assertNotNull($url);

        $this->render($entry, [
            'assetType' => null,
            'transformation' => File::getModule()->getTransformation(Transformation::NAME_ADMIN),
        ]);

        self::assertStringContainsString($url, $this->getHead());

        $this->render($entry, ['assetType' => null, 'transformation' => Transformation::NAME_ADMIN]);
        self::assertStringContainsString($url, $this->getHead());

        $this->render($entry, ['assetType' => null, 'transformation' => null]);
        self::assertStringContainsString($file->getUrl() . '"', $this->getHead());
    }

    public function testTheContainerSetsADefaultTheCallerCanOverride(): void
    {
        Yii::$container->set(MetaTags::class, ['transformation' => null]);

        try {
            $entry = $this->getEntryFromFixture('page-enabled');
            $file = $this->getAssetFromFixture('entry-asset')->file;

            $this->render($entry, ['assetType' => null]);
            self::assertStringContainsString($file->getUrl() . '"', $this->getHead());

            $this->render($entry, ['assetType' => null, 'transformation' => Transformation::NAME_OPEN_GRAPH]);
            self::assertStringContainsString('/og/', $this->getHead());
        } finally {
            Yii::$container->clear(MetaTags::class);
        }
    }

    public function testEveryAssetIsRegisteredWithoutATypeFilter(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        $this->render($entry, ['assetType' => null]);
        $head = $this->getHead();

        self::assertStringContainsString($this->getAssetFromFixture('entry-asset')->file->getFilename(), $head);
        self::assertStringContainsString($this->getAssetFromFixture('entry-meta-image')->file->getFilename(), $head);
    }

    public function testTheImagesCanBeTurnedOff(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        $this->render($entry, ['enableImages' => false]);

        self::assertStringNotContainsString('og:image', $this->getHead());
    }

    public function testTheCanonicalUrlCanBeTurnedOff(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        $route = $entry->getRoute() ?: self::fail('The entry has no route.');
        $url = Yii::$app->getUrlManager()->createAbsoluteUrl($route);

        $this->render($entry);
        self::assertStringContainsString('<link href="' . $url . '" rel="canonical">', $this->getHead());

        $this->render($entry, ['enableCanonicalUrl' => false]);
        self::assertStringNotContainsString('rel="canonical"', $this->getHead());
    }

    public function testANestedEntryListsItsAncestorsAsBreadcrumbs(): void
    {
        $parent = $this->getEntryFromFixture('page-enabled');
        $url = Yii::$app->getUrlManager()->createAbsoluteUrl($parent->getRoute() ?: self::fail('The parent has no route.'));

        $html = $this->render($this->getPreloadedEntryFromFixture('post-1'));

        self::assertStringContainsString('"@type":"BreadcrumbList"', $html);
        self::assertStringContainsString('{"@type":"ListItem","position":1,"name":"Test Page – Enabled","item":' . json_encode($url) . '}', $html);
        self::assertStringContainsString('{"@type":"ListItem","position":2,"name":"Test Child 1"}', $html);
    }

    /**
     * The ancestors come with the preload's query for the related entries, so the breadcrumbs cost nothing.
     */
    public function testTheBreadcrumbsRunNoQuery(): void
    {
        $entry = $this->getPreloadedEntryFromFixture('post-1');

        $without = $this->countQueries(fn () => $this->render($entry, ['enableBreadcrumbs' => false]));
        $with = $this->countQueries(fn () => $this->render($entry));

        self::assertSame($without, $with);
    }

    public function testTheBreadcrumbsCanBeTurnedOff(): void
    {
        $html = $this->render($this->getPreloadedEntryFromFixture('post-1'), ['enableBreadcrumbs' => false]);
        self::assertStringNotContainsString('BreadcrumbList', $html);
    }

    public function testATopLevelEntryHasNoBreadcrumbs(): void
    {
        $html = $this->render($this->getPreloadedEntryFromFixture('page-enabled'));
        self::assertStringNotContainsString('BreadcrumbList', $html);
    }

    /**
     * The status is the request's, as `SiteController` sets it: outside a draft host, the preload leaves out the draft
     * parent, which leaves the entry no trail to list.
     */
    public function testAnUnpublishedAncestorIsLeftOut(): void
    {
        ActiveQuery::setStatus(Entry::STATUS_ENABLED);

        $html = $this->render($this->getPreloadedEntryFromFixture('post-3'));
        self::assertStringNotContainsString('BreadcrumbList', $html);
    }

    public function testANestedCategoryListsItsAncestorsAsBreadcrumbs(): void
    {
        $category = Category::findOne($this->getCategoryFixtureData('child-1')['id']) ?? self::fail('No category.');

        $html = $this->render($category, ['enableSocialMetaTags' => false]);

        self::assertStringContainsString('"position":1,"name":"Root category 1","item":', $html);
        self::assertStringContainsString('{"@type":"ListItem","position":2,"name":"Child category 1"}', $html);
    }

    public function testThePageIsPartOfTheWebSite(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $url = $this->getUrl($entry);

        $this->render($entry);
        $graph = $this->getGraph();

        self::assertSame(['https://www.test.localhost/#website', "$url#webpage"], array_keys($graph));
        self::assertArrayNotHasKey('publisher', $graph['https://www.test.localhost/#website']);

        $page = $graph["$url#webpage"];

        self::assertSame('WebPage', $page['@type']);
        self::assertSame($url, $page['url']);
        self::assertSame('Test Page – Enabled', $page['name']);
        self::assertSame(Yii::$app->language, $page['inLanguage']);
        self::assertSame(['@id' => 'https://www.test.localhost/#website'], $page['isPartOf']);
        self::assertSame(StructuredData::date($entry->publish_date), $page['datePublished']);
        self::assertSame(StructuredData::date($entry->updated_at), $page['dateModified'] ?? null);
        self::assertArrayNotHasKey('breadcrumb', $page);
        self::assertArrayNotHasKey('mainEntity', $page);
    }

    public function testAConfiguredOrganizationPublishesTheWebSite(): void
    {
        Yii::$container->set(Organization::class, ['name' => 'Example GmbH']);

        $this->render($this->getEntryFromFixture('page-enabled'));
        $graph = $this->getGraph();

        self::assertSame('Example GmbH', $graph['https://www.test.localhost/#organization']['name'] ?? null);
        self::assertSame(
            ['@id' => 'https://www.test.localhost/#organization'],
            $graph['https://www.test.localhost/#website']['publisher'] ?? null,
        );
    }

    public function testANestedPagePointsToItsBreadcrumbs(): void
    {
        $this->render($this->getPreloadedEntryFromFixture('post-1'));
        $nodes = array_column($this->getGraph(), null, '@type');

        self::assertArrayHasKey('BreadcrumbList', $nodes);
        self::assertSame(['@id' => $nodes['BreadcrumbList']['@id']], $nodes['WebPage']['breadcrumb'] ?? null);
    }

    public function testACategoryIsACollectionPage(): void
    {
        $category = Category::findOne($this->getCategoryFixtureData('child-1')['id']) ?? self::fail('No category.');

        $this->render($category, ['enableSocialMetaTags' => false]);
        $pages = array_filter($this->getGraph(), fn (array $node): bool => ($node['@type'] ?? null) === 'CollectionPage');

        self::assertCount(1, $pages);
        self::assertArrayNotHasKey('datePublished', current($pages) ?: []);
    }

    public function testTheStructuredDataCanBeTurnedOff(): void
    {
        $this->render($this->getPreloadedEntryFromFixture('post-1'), ['enableStructuredData' => false]);
        self::assertSame([], $this->getGraph());
    }

    public function testTheTypeChangesThePageNode(): void
    {
        $entry = $this->getStructuredDataEntry(StructuredDataTestEntry::TYPE_ARTICLE);
        $url = $this->getUrl($entry);

        $this->render($entry);
        $page = $this->getGraph()["$url#webpage"] ?? [];

        self::assertSame('Article', $page['@type'] ?? null);
        self::assertSame('Test Page – Enabled', $page['headline'] ?? null);
    }

    public function testTheTypeCanLeaveThePageOut(): void
    {
        $entry = $this->getStructuredDataEntry(StructuredDataTestEntry::TYPE_NONE);
        $this->render($entry);

        self::assertSame(['https://www.test.localhost/#website'], array_keys($this->getGraph()));
    }

    public function testAnEventIsThePagesMainEntity(): void
    {
        $previous = Yii::$app->getTimeZone();
        Yii::$app->setTimeZone('Europe/Berlin');

        try {
            $entry = $this->getStructuredDataEntry(StructuredDataTestEntry::TYPE_EVENT);
            $entry->publish_date = new DateTime('2026-10-09 18:00:00', new \DateTimeZone('UTC'));
            $entry->end_date = new DateTime('2026-10-09 21:00:00', new \DateTimeZone('UTC'));
            $entry->venue = 'Großer Saal – 東京 🎉';
            $entry->address = 'Mu' . "\u{308}" . 'llerstraße 1, München';

            $url = $this->getUrl($entry);

            $this->render($entry);
            $graph = $this->getGraph();
            $page = $graph["$url#webpage"] ?? [];

            self::assertSame(['@id' => "$url#event"], $page['mainEntity'] ?? null);
            self::assertArrayNotHasKey('datePublished', $page);

            self::assertSame([
                '@type' => 'Event',
                '@id' => "$url#event",
                'startDate' => '2026-10-09T20:00:00+02:00',
                'endDate' => '2026-10-09T23:00:00+02:00',
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'location' => [
                    '@type' => 'Place',
                    'name' => 'Großer Saal – 東京 🎉',
                    'address' => 'Mu' . "\u{308}" . 'llerstraße 1, München',
                ],
                'name' => 'Test Page – Enabled',
                'image' => $page['primaryImageOfPage'] ?? self::fail('The page has no image.'),
                'url' => $url,
            ], $graph["$url#event"] ?? null);
        } finally {
            Yii::$app->setTimeZone($previous);
        }
    }

    public function testAnEventWithoutAPlaceIsLeftOut(): void
    {
        $entry = $this->getStructuredDataEntry(StructuredDataTestEntry::TYPE_EVENT);
        $url = $this->getUrl($entry);

        $this->render($entry);
        $graph = $this->getGraph();

        self::assertArrayNotHasKey("$url#event", $graph);
        self::assertArrayNotHasKey('mainEntity', $graph["$url#webpage"] ?? []);
    }

    private function getStructuredDataEntry(int $type): StructuredDataTestEntry
    {
        $entry = StructuredDataTestEntry::findOne($this->getEntryFixtureData('page-enabled')['id'])
            ?? self::fail('No entry.');

        $entry->type = $type;

        return $entry;
    }

    /**
     * One language is no alternative, so the hreflang links are left out entirely.
     */
    public function testTheHrefLangLinksNeedMoreThanOneLanguage(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        $this->render($entry);
        self::assertStringNotContainsString('hreflang', $this->getHead());

        $this->setUpI18nUrls();

        $this->render($entry);
        $head = $this->getHead();

        self::assertStringContainsString('hreflang="en-US"', $head);
        self::assertStringContainsString('hreflang="de"', $head);
        self::assertStringContainsString('hreflang="x-default"', $head);
    }

    private function setUpI18nUrls(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $manager = Yii::$app->getUrlManager();
        $manager->i18nUrl = true;
        $manager->languages = ['en-US' => 'en-US', 'de' => 'de'];
    }

    /**
     * @param array<string, mixed> $config the setters to call, by name
     */
    private function render(Category|Entry $model, array $config = []): string
    {
        Yii::$app->set('view', Yii::$app->getComponents()['view']);

        $widget = MetaTags::make()->model($model);

        foreach ($config as $name => $value) {
            $widget->$name($value);
        }

        self::assertSame('', $widget->__toString(), 'Everything goes into the head.');

        $nodes = array_values($this->getGraph());
        return $nodes ? StructuredData::encode($nodes) : '';
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function getGraph(): array
    {
        $view = Yii::$app->getView();
        self::assertInstanceOf(View::class, $view);

        return $view->getStructuredData();
    }

    private function getUrl(Entry $entry): string
    {
        return Yii::$app->getUrlManager()->createAbsoluteUrl($entry->getRoute() ?: self::fail('The entry has no route.'));
    }

    private function getPreloadedEntryFromFixture(string $key): Entry
    {
        $entry = $this->getEntryFromFixture($key);
        Yii::$container->get(PreloadEntrySiteRelations::class, config: ['entry' => $entry]);

        return $entry;
    }

    /**
     * The rendered head is a placeholder until the page ends, so the registered tags are read off the view.
     */
    private function getHead(): string
    {
        $view = Yii::$app->getView();

        return implode("\n", [
            ...array_map(strval(...), $view->metaTags),
            ...array_map(strval(...), $view->linkTags),
        ]);
    }
}

/**
 * @property DateTime|string|null $end_date
 * @property string|null $venue
 * @property string|null $address
 */
class StructuredDataTestEntry extends TestEntry
{
    public const int TYPE_ARTICLE = 10;
    public const int TYPE_NONE = 11;
    public const int TYPE_EVENT = 12;

    #[Override]
    public function getTypes(): array
    {
        return [
            ...parent::getTypes(),
            EntryType::make(self::TYPE_ARTICLE)
                ->name('Article')
                ->structuredData(fn (Entry $entry, array $node): array => [
                    ...$node,
                    '@type' => 'Article',
                    'headline' => $entry->name,
                ]),
            EntryType::make(self::TYPE_NONE)
                ->name('None')
                ->structuredData(fn (): ?array => null),
            EntryType::make(self::TYPE_EVENT)
                ->name('Event')
                ->schedule(false)
                ->customAttributes([
                    DateTimeCustomAttribute::make('end_date'),
                    TextCustomAttribute::make('venue'),
                    TextCustomAttribute::make('address'),
                ])
                ->structuredData(fn (Entry $entry): Event => Event::make()
                    ->startDate($entry->publish_date)
                    ->endDate($entry->getVisibleAttribute('end_date'))
                    ->location($entry->getVisibleAttribute('venue'), $entry->getVisibleAttribute('address'))),
        ];
    }
}
