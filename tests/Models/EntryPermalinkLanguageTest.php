<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;
use Yii;

/**
 * The two ways a slug can behave across languages:
 *
 * - not an `i18nAttribute`: one permalink, stored under the source language, resolvable in every language;
 * - an `i18nAttribute`: one permalink per language, each resolvable under its own language.
 */
class EntryPermalinkLanguageTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    public function testUntranslatedSlugWritesASinglePermalink(): void
    {
        $entry = $this->createEntry('contact');

        self::assertCount(1, $entry->permalinks);
        self::assertSame([Permalink::LANGUAGE_ALL], array_keys($entry->permalinks));
        self::assertSame('contact', $entry->getPermalink('en-US')->uri);
        self::assertSame('contact', $entry->getPermalink('de')->uri);
    }

    public function testUntranslatedSlugResolvesInEveryLanguage(): void
    {
        $entry = $this->createEntry('contact');

        foreach (['en-US', 'de'] as $language) {
            $permalink = Permalink::find()->whereUri('contact', $language)->one();

            self::assertNotNull($permalink, "'contact' did not resolve under $language.");
            self::assertSame($entry->id, $permalink->model_id);
            self::assertSame('contact', $entry->getFormattedSlug($language));
        }
    }

    public function testTranslatedSlugWritesAPermalinkPerLanguage(): void
    {
        $entry = $this->createTranslatedEntry(['en-US' => 'contact', 'de' => 'kontakt']);

        self::assertCount(2, $entry->permalinks);
        self::assertSame('contact', $entry->getFormattedSlug('en-US'));
        self::assertSame('kontakt', $entry->getFormattedSlug('de'));
    }

    public function testTranslatedSlugResolvesUnderItsOwnLanguage(): void
    {
        $entry = $this->createTranslatedEntry(['en-US' => 'contact', 'de' => 'kontakt']);

        self::assertSame($entry->id, Permalink::find()->whereUri('contact', 'en-US')->one()?->model_id);
        self::assertSame($entry->id, Permalink::find()->whereUri('kontakt', 'de')->one()?->model_id);
    }

    /**
     * A translated slug has a real record per language and no language-agnostic one, so each URL resolves only under
     * its own language — in either direction. The source-language URL leaking into other languages is exactly what
     * the {@see Permalink::LANGUAGE_ALL} sentinel prevents: only an untranslated slug gets that fallback.
     */
    public function testTranslatedSlugDoesNotResolveUnderTheWrongLanguage(): void
    {
        $this->createTranslatedEntry(['en-US' => 'contact', 'de' => 'kontakt']);

        self::assertNull(Permalink::find()->whereUri('kontakt', 'en-US')->one());
        self::assertNull(Permalink::find()->whereUri('contact', 'de')->one());
    }

    public function testSlugBecomingTranslatedReplacesTheFallbackRecord(): void
    {
        $entry = $this->createEntry('contact');
        $entry->i18nAttributes = ['slug'];
        $entry->setAttributes(['slug_de' => 'kontakt'], false);

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        self::assertEqualsCanonicalizing(['en-US', 'de'], array_keys($entry->permalinks));
        self::assertSame('contact', $entry->getPermalink('en-US')->uri);
        self::assertSame('kontakt', $entry->getPermalink('de')->uri);
        self::assertNull(Permalink::find()->whereUri('contact', 'de')->one());
    }

    public function testSlugBecomingUntranslatedReplacesThePerLanguageRecords(): void
    {
        $entry = $this->createTranslatedEntry(['en-US' => 'contact', 'de' => 'kontakt']);
        $entry->i18nAttributes = [];
        $entry->slug = 'contact-us';

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        self::assertSame([Permalink::LANGUAGE_ALL], array_keys($entry->permalinks));
        self::assertSame('contact-us', $entry->getFormattedSlug('de'));
        self::assertNull(Permalink::find()->whereUri('kontakt', 'de')->one());
    }

    protected function createEntry(string $slug): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = ucfirst($slug);
        $entry->slug = $slug;

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }

    /**
     * @param array<string, string> $slugs the slug for each language
     */
    protected function createTranslatedEntry(array $slugs): TestEntry
    {
        $entry = TestEntry::create();
        $entry->i18nAttributes = ['slug'];
        $entry->name = 'Contact';

        foreach ($slugs as $language => $slug) {
            $entry->setAttribute($entry->getI18nAttributeName('slug', $language), $slug);
        }

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }
}
