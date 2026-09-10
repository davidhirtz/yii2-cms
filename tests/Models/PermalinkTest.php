<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
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
     * for {@see PermalinkQuery::whereUri()} to fall back to, so another language does not resolve it.
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
        $agnostic = $this->makePermalink('shared', 'shared', modelId: 1);
        $agnostic->language = Permalink::LANGUAGE_ALL;
        self::assertTrue($agnostic->insert());

        $translated = $this->makePermalink('shared', 'shared', modelId: 2);
        $translated->language = 'de';
        self::assertTrue($translated->insert());

        self::assertSame($translated->id, Permalink::find()->whereUri('shared', 'de')->one()?->id);
        self::assertSame($agnostic->id, Permalink::find()->whereUri('shared', 'fr')->one()?->id);
    }

    public function testUriMustBeUniquePerLanguage(): void
    {
        $this->createPermalink('blog/hello-world', 'hello-world', modelId: 1);

        $duplicate = $this->makePermalink('blog/hello-world', 'hello-world', modelId: 2);

        self::assertFalse($duplicate->insert());
        self::assertArrayHasKey('uri', $duplicate->getErrors());
    }

    public function testSameUriIsAllowedInAnotherLanguage(): void
    {
        $this->createPermalink('blog/hello-world', 'hello-world', modelId: 1);

        $permalink = $this->makePermalink('blog/hello-world', 'hello-world', modelId: 1);
        $permalink->language = 'de';

        self::assertTrue($permalink->insert());
    }

    public function testModelCanOnlyHaveOnePermalinkPerLanguage(): void
    {
        $this->createPermalink('blog/hello-world', 'hello-world', modelId: 1);

        $duplicate = $this->makePermalink('blog/goodbye-world', 'goodbye-world', modelId: 1);

        self::assertFalse($duplicate->insert());
        self::assertArrayHasKey('model_id', $duplicate->getErrors());
    }

    public function testUriShadowingAnImmutableRouteParamIsRejected(): void
    {
        $param = Yii::$app->getUrlManager()->getImmutableRuleParams()[0] ?? null;
        self::assertNotNull($param, 'Expected at least one immutable rule param to test against.');

        $permalink = $this->makePermalink($param, $param);

        self::assertFalse($permalink->insert());
        self::assertArrayHasKey('uri', $permalink->getErrors());
    }

    public function testParentRelationIsPopulated(): void
    {
        $parent = $this->createPermalink('blog', 'blog', modelId: 1);

        $child = $this->makePermalink('blog/hello-world', 'hello-world', modelId: 2);
        $child->populateParentRelation($parent);

        self::assertTrue($child->insert());
        self::assertSame($parent->id, $child->parent_id);
        self::assertSame($parent->id, Permalink::findOne($child->id)->parent->id);
    }

    public function testIsModelMatchesSubclasses(): void
    {
        $permalink = $this->createPermalink('blog/hello-world', 'hello-world');

        self::assertTrue($permalink->isModel(Entry::class));
        self::assertFalse($permalink->isModel(Permalink::class));
    }

    protected function makePermalink(string $uri, string $slug, int $modelId = 1): Permalink
    {
        $permalink = Permalink::create();
        $permalink->language = Yii::$app->language;
        $permalink->uri = $uri;
        $permalink->slug = $slug;
        $permalink->model = Entry::class;
        $permalink->model_id = $modelId;

        return $permalink;
    }

    protected function createPermalink(string $uri, string $slug, int $modelId = 1): Permalink
    {
        $permalink = $this->makePermalink($uri, $slug, $modelId);
        self::assertTrue($permalink->insert(), implode(' ', $permalink->getErrorSummary(true)));

        return $permalink;
    }
}
