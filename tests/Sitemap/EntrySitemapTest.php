<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Sitemap;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Sitemap\EntrySitemap;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Override;
use Yii;

class EntrySitemapTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->set('sitemap', ['class' => Sitemap::class]);
        Yii::$app->getUrlManager()->i18nUrl = false;
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Entry::class);
        Entry::instance(true);

        parent::tearDown();
    }

    public function testOneUrlPerPublishedRecord(): void
    {
        $this->createEntry('First', 'first');
        $this->createEntry('Second', 'second');
        $this->createEntry('Hidden', 'hidden', Entry::STATUS_DISABLED);

        $urls = $this->createSitemap()->generateUrls();

        self::assertCount(2, $urls);

        self::assertIsArray($urls[0]);
        self::assertSame('/cms/site/view', $urls[0]['loc'][0]);
        self::assertSame('first', $urls[0]['loc']['slug']);
        self::assertNull($urls[0]['loc']['language']);
        self::assertNotEmpty($urls[0]['lastmod']);
    }

    public function testThePageCountMatchesWhatIsGenerated(): void
    {
        $this->createEntry('First', 'first');
        $this->createEntry('Second', 'second');

        $sitemap = $this->createSitemap();

        self::assertSame(2, $sitemap->getRecordCount());
        self::assertSame(1, $sitemap->getPageCount());
    }

    public function testEveryLanguageGetsItsOwnUrl(): void
    {
        $this->setUpI18nUrls();
        $this->createEntry('First', 'first');

        $sitemap = $this->createSitemap();
        $urls = $sitemap->generateUrls();

        self::assertCount(2, $urls);

        self::assertIsArray($urls[0]);
        self::assertSame(['en-US', 'de'], array_column(array_column($urls, 'loc'), 'language'));

        self::assertSame(1, $sitemap->getRecordCount());
    }

    /**
     * The language is switched per record so the translated attributes resolve, and it has to be put back: whatever
     * renders the sitemap — and everything after it — runs in the request's own language.
     */
    public function testTheApplicationLanguageIsRestored(): void
    {
        $this->setUpI18nUrls();
        $this->createEntry('First', 'first');

        $this->createSitemap()->generateUrls();

        self::assertSame('en-US', Yii::$app->language);
    }

    public function testAnIndexEntryPointsAtTheSiteRoot(): void
    {
        $this->createEntry('Home', 'home');

        $url = $this->createSitemap()->generateUrls()[0];
        self::assertIsArray($url);

        self::assertSame('/cms/site/index', $url['loc'][0]);
        self::assertArrayNotHasKey('slug', $url['loc']);
    }

    /**
     * With an index the records are paged, and the page size is shared between the languages: a record produces one
     * URL per language, so a page holds half the records once two languages are configured.
     */
    public function testTheSitemapIndexPagesTheRecords(): void
    {
        $this->createEntry('First', 'first');
        $this->createEntry('Second', 'second');
        $this->createEntry('Third', 'third');

        Yii::$app->sitemap->useSitemapIndex = true;
        Yii::$app->sitemap->maxUrlCount = 2;

        $sitemap = $this->createSitemap();

        self::assertSame(2, $sitemap->getRecordsPerPage());
        self::assertSame(2, $sitemap->getPageCount());
        self::assertCount(2, $sitemap->generateUrls(0));
        self::assertCount(1, $sitemap->generateUrls(1));
    }

    /**
     * An odd number of URLs per page is what broke the paging: the records per page were a float, which the query
     * builder drops, and every page then held every record.
     */
    public function testThePageSizeStaysAnIntegerAcrossLanguages(): void
    {
        $this->setUpI18nUrls();

        $this->createEntry('First', 'first');
        $this->createEntry('Second', 'second');
        $this->createEntry('Third', 'third');

        Yii::$app->sitemap->useSitemapIndex = true;
        Yii::$app->sitemap->maxUrlCount = 3;

        $sitemap = $this->createSitemap();

        self::assertSame(1, $sitemap->getRecordsPerPage());
        self::assertSame(3, $sitemap->getPageCount());

        self::assertCount(2, $sitemap->generateUrls(0));
        self::assertCount(2, $sitemap->generateUrls(2));
    }

    private function createSitemap(): EntrySitemap
    {
        /** @var EntrySitemap $sitemap */
        $sitemap = Yii::createObject(EntrySitemap::class);
        return $sitemap;
    }

    /**
     * `ModelSitemap::getLanguages()` asks the shared `instance()` for its `i18nAttributes`, so those have to be
     * configured through the container rather than set on a record.
     */
    private function setUpI18nUrls(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $manager = Yii::$app->getUrlManager();
        $manager->i18nUrl = true;
        $manager->languages = ['en-US' => 'en-US', 'de' => 'de'];

        Yii::$container->setDefinitions([
            Entry::class => ['i18nAttributes' => ['name', 'slug']],
        ]);

        Entry::instance(true);
    }

    /**
     * `Entry::hasRoute()` answers `false` for an entry with neither a section nor a child, so an entry that is to
     * appear in the sitemap at all needs one.
     */
    private function createEntry(string $name, string $slug, int $status = Entry::STATUS_ENABLED): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = $status;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = $slug;

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        $section = Section::create();
        $section->loadDefaultValues();
        $section->status = Section::STATUS_ENABLED;
        $section->type = Section::TYPE_DEFAULT;
        $section->name = 'Content';
        $section->populateEntryRelation($entry);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $entry->refresh();

        return $entry;
    }
}
