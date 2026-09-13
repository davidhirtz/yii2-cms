<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Web\Sitemap;
use Override;
use Yii;

class SitemapTraitTest extends TestCase
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

        $urls = Entry::instance()->generateSitemapUrls();

        self::assertCount(2, $urls);
        self::assertSame('/cms/site/view', $urls[0]['loc'][0]);
        self::assertSame('first', $urls[0]['loc']['slug']);
        self::assertNull($urls[0]['loc']['language']);
        self::assertNotEmpty($urls[0]['lastmod']);
    }

    public function testTheCountMatchesWhatIsGenerated(): void
    {
        $this->createEntry('First', 'first');
        $this->createEntry('Second', 'second');

        self::assertSame(2, Entry::instance()->getSitemapUrlCount());
    }

    public function testEveryLanguageGetsItsOwnUrl(): void
    {
        $this->setUpI18nUrls();
        $this->createEntry('First', 'first');

        $urls = Entry::instance()->generateSitemapUrls();

        self::assertCount(2, $urls);
        self::assertSame(['en-US', 'de'], array_column(array_column($urls, 'loc'), 'language'));

        self::assertSame(2, Entry::instance()->getSitemapUrlCount());
    }

    /**
     * The language is switched per record so the translated attributes resolve, and it has to be put back: whatever
     * renders the sitemap — and everything after it — runs in the request's own language.
     */
    public function testTheApplicationLanguageIsRestored(): void
    {
        $this->setUpI18nUrls();
        $this->createEntry('First', 'first');

        Entry::instance()->generateSitemapUrls();

        self::assertSame('en-US', Yii::$app->language);
    }

    public function testAnIndexEntryPointsAtTheSiteRoot(): void
    {
        $entry = $this->createEntry('Home', 'home');

        self::assertSame('/cms/site/index', $entry->getSitemapUrl()['loc'][0]);
        self::assertArrayNotHasKey('slug', $entry->getSitemapUrl()['loc']);
    }

    public function testARecordThatIsNotPublishedIsLeftOut(): void
    {
        $entry = $this->createEntry('Hidden', 'hidden', Entry::STATUS_DISABLED);

        self::assertFalse($entry->getSitemapUrl());
    }

    /**
     * With an index the records are paged, and the page size is shared between the languages.
     */
    public function testTheSitemapIndexPagesTheRecords(): void
    {
        $this->createEntry('First', 'first');
        $this->createEntry('Second', 'second');
        $this->createEntry('Third', 'third');

        Yii::$app->sitemap->useSitemapIndex = true;
        Yii::$app->sitemap->maxUrlCount = 2;

        self::assertCount(2, Entry::instance()->generateSitemapUrls());
        self::assertCount(1, Entry::instance()->generateSitemapUrls(1));
    }

    /**
     * `getSitemapLanguages()` asks the shared `instance()` for its `i18nAttributes`, so those have to be configured
     * through the container rather than set on a record.
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
