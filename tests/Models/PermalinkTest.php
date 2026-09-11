<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Yii;

class PermalinkTest extends TestCase
{
    public function testPermalinkIsFoundByUri(): void
    {
        $permalink = $this->createPermalink('blog/hello-world', 'hello-world');

        $found = Permalink::find()
            ->whereUri('blog/hello-world')
            ->one();

        self::assertNotNull($found);
        self::assertSame($permalink->id, $found->id);
    }

    /**
     * The lookup must not depend on the caller trimming the request path.
     */
    public function testUriIsMatchedWithoutSurroundingSlashes(): void
    {
        $this->createPermalink('blog/hello-world', 'hello-world');

        self::assertNotNull(Permalink::find()->whereUri('/blog/hello-world/')->one());
    }

    /**
     * A permalink stored under a specific non-source language is scoped to it: there is no source-language record
     * for {@see \Hirtz\Cms\Models\Queries\PermalinkQuery::whereUri()} to fall back to, so another language does not
     * resolve it.
     */
    public function testPermalinkIsNotFoundInAnotherLanguage(): void
    {
        $permalink = $this->makePermalink('blog/hello-world', 'hello-world');
        $permalink->language = 'de';
        self::assertTrue($permalink->insert());

        self::assertNull(Permalink::find()->whereUri('blog/hello-world', 'fr')->one());
    }

    /**
     * An untranslated slug is stored once, under {@see Permalink::LANGUAGE_ALL}, and must resolve in every language.
     */
    public function testLanguageAgnosticPermalinkResolvesInEveryLanguage(): void
    {
        $permalink = $this->makePermalink('blog/hello-world', 'hello-world');
        $permalink->language = Permalink::LANGUAGE_ALL;
        self::assertTrue($permalink->insert(), implode(' ', $permalink->getErrorSummary(true)));

        foreach (['en-US', 'de', 'fr'] as $language) {
            $found = Permalink::find()->whereUri('blog/hello-world', $language)->one();

            self::assertNotNull($found, "Did not resolve under $language.");
            self::assertSame($permalink->id, $found->id);
        }
    }

    /**
     * When the requested language has its own record, it wins over the language-agnostic fallback.
     */
    public function testExactLanguageMatchWinsOverLanguageAgnosticFallback(): void
    {
        $agnostic = $this->makePermalink('shared', 'shared');
        $agnostic->language = Permalink::LANGUAGE_ALL;
        self::assertTrue($agnostic->insert());

        $translated = $this->makePermalink('shared', 'shared');
        $translated->language = 'de';
        self::assertTrue($translated->insert());

        self::assertSame($translated->id, Permalink::find()->whereUri('shared', 'de')->one()?->id);
        self::assertSame($agnostic->id, Permalink::find()->whereUri('shared', 'fr')->one()?->id);
    }

    public function testUriMustBeUniquePerLanguage(): void
    {
        $this->createPermalink('blog/hello-world', 'hello-world');

        $duplicate = $this->makePermalink('blog/hello-world', 'hello-world');

        self::assertFalse($duplicate->insert());
        self::assertArrayHasKey('uri', $duplicate->getErrors());
    }

    public function testSameUriIsAllowedInAnotherLanguage(): void
    {
        $this->createPermalink('blog/hello-world', 'hello-world');

        $permalink = $this->makePermalink('blog/hello-world', 'hello-world');
        $permalink->language = 'de';

        self::assertTrue($permalink->insert());
    }

    public function testEntryCanOnlyHaveOnePermalinkPerLanguage(): void
    {
        $entry = $this->createEntry();
        $this->createPermalink('blog/hello-world', 'hello-world', $entry);

        $duplicate = $this->makePermalink('blog/goodbye-world', 'goodbye-world', $entry);

        self::assertFalse($duplicate->insert());
        self::assertArrayHasKey('entry_id', $duplicate->getErrors());
    }

    public function testUriShadowingAnImmutableRouteParamIsRejected(): void
    {
        $param = Yii::$app->getUrlManager()->getImmutableRuleParams()[0] ?? null;
        self::assertNotNull($param, 'Expected at least one immutable rule param to test against.');

        $permalink = $this->makePermalink($param, $param);

        self::assertFalse($permalink->insert());
        self::assertArrayHasKey('uri', $permalink->getErrors());
    }

    public function testPermalinkBelongsToItsEntry(): void
    {
        $entry = $this->createEntry();
        $permalink = $this->createPermalink('blog/hello-world', 'hello-world', $entry);

        self::assertSame($entry->id, $permalink->entry->id);
        self::assertSame($entry->tenant_id, $permalink->tenant_id);
    }

    protected function makePermalink(string $uri, string $slug, ?TestEntry $entry = null): Permalink
    {
        $entry ??= $this->createEntry();

        $permalink = Permalink::create();
        $permalink->language = Yii::$app->language;
        $permalink->uri = $uri;
        $permalink->slug = $slug;
        $permalink->entry_id = $entry->id;
        $permalink->tenant_id = $entry->tenant_id;

        return $permalink;
    }

    protected function createPermalink(string $uri, string $slug, ?TestEntry $entry = null): Permalink
    {
        $permalink = $this->makePermalink($uri, $slug, $entry);
        self::assertTrue($permalink->insert(), implode(' ', $permalink->getErrorSummary(true)));

        return $permalink;
    }

    /**
     * The permalink written on save is removed again, so this class controls every record it asserts on.
     */
    protected function createEntry(): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = 'Entry ' . uniqid();

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        Permalink::deleteAll(['entry_id' => $entry->id]);
        $entry->populateRelation('permalinks', []);

        return $entry;
    }
}
