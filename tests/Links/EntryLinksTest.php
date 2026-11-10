<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Links;

use Hirtz\Cms\Links\EntryLinks;
use Hirtz\Cms\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Cms\Models\CustomAttributes\LinkCustomAttribute;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Override;
use Yii;

class EntryLinksTest extends TestCase
{
    use ModuleTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        ActiveQuery::setStatus(TestEntry::STATUS_ENABLED);
        EntryLinks::reset();
    }

    #[Override]
    protected function tearDown(): void
    {
        EntryLinks::reset();
        ActiveQuery::resetStatus();

        parent::tearDown();
    }

    public function testAValueIsParsedIntoIdAndFragment(): void
    {
        self::assertSame(['id' => 12, 'fragment' => ''], EntryLinks::parse('entry:12'));
        self::assertSame(['id' => 12, 'fragment' => '#team'], EntryLinks::parse('entry:12#team'));
        self::assertNull(EntryLinks::parse('entry:0'));
        self::assertNull(EntryLinks::parse('entry:abc'));
        self::assertNull(EntryLinks::parse('entry:12?x=1'));
        self::assertNull(EntryLinks::parse('/entry:12'));
        self::assertNull(EntryLinks::parse(null));
    }

    public function testTheIdsOfTheLinksInHtmlAreFound(): void
    {
        self::assertSame([], EntryLinks::findIds('<p>No link</p>'));
        self::assertSame([12, 7], EntryLinks::findIds('<a href="entry:12">A</a> <a href="entry:7#x">B</a> <a href="entry:12">C</a>'));
    }

    public function testTheSkeletonAttributesResolveToTheCmsOnes(): void
    {
        self::assertInstanceOf(HtmlCustomAttribute::class, \Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute::make('content'));
        self::assertInstanceOf(LinkCustomAttribute::class, \Hirtz\Skeleton\Models\CustomAttributes\LinkCustomAttribute::make('link'));
    }

    public function testAReachableLinkGetsItsUrlAndKeepsItsFragmentAndMarkup(): void
    {
        $target = $this->createEntry('karriere', 'Karriere 東京 🚀');

        $html = EntryLinks::replaceHtml("<p>Ån <a href=\"entry:$target->id#team\" class=\"btn\"><strong>Karriere 東京 🚀</strong></a>.</p>");

        self::assertMatchesRegularExpression('#^<p>Ån <a href="[^"]*/karriere\#team" class="btn"><strong>Karriere 東京 🚀</strong></a>\.</p>$#u', $html);
    }

    public function testAnUnreachableLinkIsUnwrappedToItsText(): void
    {
        $disabled = $this->createEntry('disabled', 'Disabled');
        $disabled->updateAttributes(['status' => Entry::STATUS_DISABLED]);

        $html = EntryLinks::replaceHtml("<a href=\"entry:$disabled->id\"><strong>Ågot</strong></a> <a href=\"entry:999999\">Gone</a>");

        self::assertSame('<strong>Ågot</strong> Gone', $html);
    }

    public function testAnEntryWithoutContentHasNoUrl(): void
    {
        $entry = TestEntry::create();
        $entry->name = 'Empty';
        $entry->slug = 'empty';
        self::assertTrue($entry->save());

        self::assertNull(EntryLinks::getUrl("entry:$entry->id"));
    }

    public function testVagueTextGetsTheTargetsNameAsLabel(): void
    {
        $target = $this->createEntry('karriere', 'Karriere');
        $link = static fn (string $text): string => EntryLinks::replaceHtml("<a href=\"entry:$target->id\">$text</a>");

        self::assertStringContainsString('aria-label="Read more: Karriere"', $link('Read more'));
        self::assertStringContainsString('aria-label="Read more: Karriere"', $link(" Read\n <em>more</em> "));
        self::assertStringNotContainsString('aria-label', $link('Zur Karriereseite'));
        self::assertStringNotContainsString('aria-label', $link('KARRIERE'));
        self::assertStringContainsString('aria-label="Karriere"', $link('<img src="/icon.svg" alt="">'));
    }

    public function testTheLabelIsEncodedAndComparesNormalizedText(): void
    {
        $target = $this->createEntry('mueller', 'Müller & "Söhne" Å 東京 🚀');

        $html = EntryLinks::replaceHtml("<a href=\"entry:$target->id\">Mu\u{308}ller &amp; \"Söhne\" Å 東京 🚀</a>");
        self::assertStringNotContainsString('aria-label', $html);

        $html = EntryLinks::replaceHtml("<a href=\"entry:$target->id\">Mehr</a>");
        self::assertStringContainsString('aria-label="Mehr: Müller &amp; &quot;Söhne&quot; Å 東京 🚀"', $html);
    }

    public function testAnEditorsOwnLabelIsKept(): void
    {
        $target = $this->createEntry('karriere', 'Karriere');
        $html = EntryLinks::replaceHtml("<a href=\"entry:$target->id\" aria-label=\"Read more: Stellen Å 東京 🚀\">Read more</a>");

        self::assertSame(1, substr_count($html, 'aria-label='));
        self::assertStringContainsString('aria-label="Read more: Stellen Å 東京 🚀"', $html);
    }

    public function testTheLabelCanBeTurnedOff(): void
    {
        $target = $this->createEntry('karriere', 'Karriere');
        static::getModule()->entryLinkAriaLabels = false;

        self::assertStringNotContainsString('aria-label', EntryLinks::replaceHtml("<a href=\"entry:$target->id\">Read more</a>"));
    }

    public function testALinkAttributeIsAUrlOrNull(): void
    {
        $target = $this->createEntry('karriere', 'Karriere');

        self::assertStringEndsWith('/karriere#team', (string)EntryLinks::getUrl("entry:$target->id#team"));
        self::assertSame('https://example.com/ü', EntryLinks::getUrl('https://example.com/ü'));
        self::assertSame('/über-uns', EntryLinks::getUrl('/über-uns'));
        self::assertNull(EntryLinks::getUrl('entry:999999'));
        self::assertNull(EntryLinks::getUrl('entry:abc'));
        self::assertNull(EntryLinks::getUrl(''));
    }

    /**
     * `getFormattedSlug()` would fall back to the English URI, which 404s under German.
     */
    public function testAnEntryWithoutAPermalinkInTheLanguageHasNoUrl(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $entry = TestEntry::create();
        $entry->i18nAttributes = ['name', 'slug'];
        $entry->name = 'Contact';
        $entry->slug = 'contact';
        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));
        $this->addSection($entry);

        // As a tenant offering English alone leaves it.
        Permalink::deleteAll(['entry_id' => $entry->id, 'language' => 'de']);

        self::assertStringEndsWith('/contact', (string)EntryLinks::getUrl("entry:$entry->id", 'en-US'));
        self::assertNull(EntryLinks::getUrl("entry:$entry->id", 'de'));
    }

    public function testAnotherTenantsEntryLinksToItsHostAndADisabledTenantsToNothing(): void
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = 'Second Tenant';
        $tenant->language = Yii::$app->sourceLanguage;
        $tenant->url = 'https://www.second-domain.localhost';
        self::assertTrue($tenant->save(), implode(' ', $tenant->getErrorSummary(true)));
        TenantCollection::invalidateCache();

        $entry = $this->createEntry('other', 'Other', $tenant);

        self::assertSame('https://www.second-domain.localhost/other', EntryLinks::getUrl("entry:$entry->id"));

        $tenant->updateAttributes(['status' => Tenant::STATUS_DISABLED]);
        TenantCollection::invalidateCache();
        EntryLinks::reset();

        self::assertNull(EntryLinks::getUrl("entry:$entry->id"));
    }

    public function testTheLinksOfManyModelsLoadInOneBatch(): void
    {
        $targets = [];
        $sections = [];

        for ($i = 1; $i <= 20; $i++) {
            $targets[] = $this->createEntry("target-$i", "Target $i");
        }

        $source = $this->createEntry('source', 'Source');

        foreach ($targets as $i => $target) {
            $sections[] = $this->addSection($source, "<p><a href=\"entry:$target->id\">Target $i</a></p>");
        }

        EntryLinks::reset();

        // The entries and their permalinks, whatever the number of links.
        self::assertSame(2, $this->countQueries(fn () => EntryLinks::preload($sections)));
        self::assertSame(0, $this->countQueries(function () use ($sections): void {
            foreach ($sections as $section) {
                self::assertStringContainsString('/target-', (string)$section->getVisibleAttribute('content'));
            }
        }));
    }

    protected function createEntry(string $slug, string $name, ?Tenant $tenant = null): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = $name;
        $entry->slug = $slug;

        if ($tenant) {
            $entry->populateTenantRelation($tenant);
        }

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));
        $this->addSection($entry);

        return $entry;
    }

    /**
     * An entry has a route only with content.
     */
    protected function addSection(TestEntry $entry, string $content = '<p>Text</p>'): TestSection
    {
        $section = TestSection::instantiateByType(TestSection::TYPE_TEXT_COLUMN);
        $section->populateEntryRelation($entry);
        $section->content = $content;

        self::assertTrue($section->insert(), implode(' ', $section->getErrorSummary(true)));
        $entry->section_count = (int)$entry->section_count ?: 1;

        return $section;
    }
}
