<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\MetaTags;
use Hirtz\Media\Models\File;
use Hirtz\Media\Transformations\Transformation;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
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
        $entry->content = 'Some content';

        $this->render($entry);
        self::assertStringContainsString('Some content', $this->getHead());

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

    /**
     * The v2 property array fails on the protected options; a closure definition calls the setters.
     */
    public function testTheContainerSetsADefaultTheCallerCanOverride(): void
    {
        Yii::$container->set(MetaTags::class, fn (): MetaTags => (new MetaTags())->transformation(null));

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

    public function testTheCanonicalUrlIsOptional(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');

        $this->render($entry);
        self::assertStringNotContainsString('rel="canonical"', $this->getHead());

        $this->render($entry, ['enableCanonicalUrl' => true]);
        self::assertStringContainsString('rel="canonical"', $this->getHead());
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
    private function render(Entry $entry, array $config = []): void
    {
        Yii::$app->set('view', Yii::$app->getComponents()['view']);

        $widget = MetaTags::make()->model($entry);

        foreach ($config as $name => $value) {
            $widget->$name($value);
        }

        $widget->__toString();
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
