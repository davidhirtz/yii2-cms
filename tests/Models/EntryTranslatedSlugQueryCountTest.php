<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;
use Yii;

/**
 * A translated slug is one virtual attribute per language, each backed by its own permalink. Reading the one the URI
 * lookup matched must not fetch the others: that turns every site request into a second permalink query.
 */
class EntryTranslatedSlugQueryCountTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        Yii::$container->setDefinitions([
            Entry::class => ['class' => TestEntry::class, 'i18nAttributes' => ['slug']],
            TestEntry::class => ['i18nAttributes' => ['slug']],
        ]);

        Entry::instance(true);
        TestEntry::instance(true);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Entry::class);
        Yii::$container->clear(TestEntry::class);

        Entry::instance(true);
        TestEntry::instance(true);

        parent::tearDown();
    }

    public function testReadingTheMatchedSlugDoesNotLoadThePermalinks(): void
    {
        $this->createEntry(['en-US' => 'contact', 'de' => 'kontakt']);

        $entry = null;
        $slug = null;

        $count = $this->countQueries(function () use (&$entry, &$slug): void {
            $entry = Entry::find()->whereUri('contact')->one();
            $slug = $entry?->slug;
        });

        self::assertSame('contact', $slug);
        self::assertSame(1, $count);
        self::assertFalse($entry->isRelationPopulated('permalinks'), 'Reading the slug loaded the permalinks.');
    }

    /**
     * The other languages are not in the matched record, so reading one still loads the relation — once.
     */
    public function testReadingAnotherLanguagesSlugLoadsThePermalinksOnce(): void
    {
        $this->createEntry(['en-US' => 'contact', 'de' => 'kontakt']);

        $entry = Entry::find()->whereUri('contact')->one();
        $slug = null;

        $count = $this->countQueries(function () use ($entry, &$slug): void {
            $slug = $entry->slug_de;
            $entry->getI18nAttribute('slug', 'de');
        });

        self::assertSame('kontakt', $slug);
        self::assertSame(1, $count);
    }

    /**
     * @param array<string, string> $slugs the slug for each language
     */
    protected function createEntry(array $slugs): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = 'Contact';

        foreach ($slugs as $language => $slug) {
            $entry->setAttribute($entry->getI18nAttributeName('slug', $language), $slug);
        }

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }
}
