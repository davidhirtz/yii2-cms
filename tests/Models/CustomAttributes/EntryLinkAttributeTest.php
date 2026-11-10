<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\CustomAttributes;

use Hirtz\Cms\Links\EntryLinks;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Cms\Models\CustomAttributes\LinkCustomAttribute;
use Hirtz\Tenant\Models\Tenant;
use Override;
use Yii;

class EntryLinkAttributeTest extends TestCase
{
    use CmsFixtureTrait;

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

    public function testTheEntrySchemeSurvivesThePurifierAndNothingElseDoes(): void
    {
        $section = $this->createSection('<a href="entry:12#a">Å</a> <a href="entry:abc">B</a> <a href="entry:-1">C</a> '
            . '<a href="entry:12?x=1">D</a> <a href="entry://12">E</a> <a href="https://example.test">F</a>');

        self::assertTrue($section->validate(['content']));
        self::assertSame(
            '<p><a href="entry:12#a">Å</a> <a>B</a> <a>C</a> <a>D</a> <a>E</a> '
            . '<a href="https://example.test" target="_blank" rel="noreferrer noopener">F</a></p>',
            $section->content,
        );
    }

    public function testTheVisibleHtmlIsResolved(): void
    {
        $section = $this->createSection('<p><a href="entry:1">Test Page – Enabled</a> <a href="entry:3">Disabled</a></p>');

        self::assertSame('<p><a href="https://www.domain.localhost/test-1">Test Page – Enabled</a> Disabled</p>', $section->getVisibleAttribute('content'));
        self::assertStringContainsString('entry:1', (string)$section->content);
    }

    public function testALinkTakesAUrlOrAnEntryOfTheScope(): void
    {
        self::assertTrue($this->validateLink('https://example.com/über'));
        self::assertTrue($this->validateLink('/über-uns#team'));
        self::assertTrue($this->validateLink('entry:1'));
        self::assertTrue($this->validateLink('entry:1#team'));

        self::assertFalse($this->validateLink('entry:abc'));
        self::assertFalse($this->validateLink('entry:999999'));
        self::assertFalse($this->validateLink('entry:4'), 'A post is not a type the attribute allows.');
        self::assertFalse($this->validateLink('example.com'));
    }

    public function testAnEntryOfAnotherTenantIsRefusedUnlessAnyTenantIsAllowed(): void
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = 'Second Tenant';
        $tenant->language = Yii::$app->sourceLanguage;
        $tenant->url = 'https://www.second-domain.localhost';
        self::assertTrue($tenant->save(), implode(' ', $tenant->getErrorSummary(true)));

        $entry = TestEntry::create();
        $entry->name = 'Other';
        $entry->slug = 'other';
        $entry->populateTenantRelation($tenant);
        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        self::assertFalse($this->validateLink("entry:$entry->id"));
        self::assertTrue($this->validateLink("entry:$entry->id", AnyTenantLinkSection::class));
    }

    /**
     * A target that left the scope since must not block an unrelated save.
     */
    public function testAStoredTargetOutOfScopeStillSaves(): void
    {
        $section = $this->createSection('<p>Text</p>');
        $section->link = 'entry:4';
        self::assertSame(1, $section->updateAttributes(['custom_attributes' => $section->getSerializedCustomAttributes()]));

        $section = LinkSection::findOne($section->id);
        self::assertSame('entry:4', $section?->link);
        $section->content = '<p>Changed</p>';

        self::assertTrue($section->validate(), implode(' ', $section->getErrorSummary(true)));
    }

    public function testTheVisibleLinkIsAUrlOrNull(): void
    {
        $section = $this->createSection('<p>Text</p>');

        $section->link = 'entry:1#team';
        self::assertSame('https://www.domain.localhost/test-1#team', $section->getVisibleAttribute('link'));

        $section->link = 'entry:3';
        self::assertNull($section->getVisibleAttribute('link'));

        $section->link = '/über-uns';
        self::assertSame('/über-uns', $section->getVisibleAttribute('link'));
    }

    public function testTheTrailShowsTheEntryName(): void
    {
        $section = $this->createSection('<p>Text</p>');
        $definition = $section->getCustomAttribute('link') ?? self::fail('No definition.');

        self::assertSame('Test Page – Enabled (entry:1)', $definition->formatValue($section, 'entry:1'));
        self::assertSame('/x', $definition->formatValue($section, '/x'));
    }

    /**
     * @param class-string<LinkSection> $class
     */
    private function validateLink(string $value, string $class = LinkSection::class): bool
    {
        $section = $this->createSection('<p>Text</p>', $class);
        $section->link = $value;

        return $section->validate(['link']);
    }

    /**
     * @param class-string<LinkSection> $class
     */
    private function createSection(string $content, string $class = LinkSection::class): LinkSection
    {
        $section = $class::instantiateByType(TestSection::TYPE_TEXT_COLUMN);
        $section->populateEntryRelation($this->getEntryFromFixture('page-enabled'));
        $section->content = $content;

        if ($section->getIsNewRecord() && $content === '<p>Text</p>') {
            self::assertTrue($section->insert(), implode(' ', $section->getErrorSummary(true)));
        }

        return $section;
    }
}

/**
 * @property string|null $link
 */
class LinkSection extends TestSection
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            ...parent::getCustomAttributes(),
            $this->getLinkAttribute(),
        ];
    }

    protected function getLinkAttribute(): LinkCustomAttribute
    {
        return LinkCustomAttribute::make('link')->types(TestEntry::TYPE_PAGE);
    }
}

class AnyTenantLinkSection extends LinkSection
{
    #[Override]
    protected function getLinkAttribute(): LinkCustomAttribute
    {
        return LinkCustomAttribute::make('link')->anyTenant();
    }
}
