<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\Artwork;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Figcaption;
use Hirtz\Skeleton\Html\Figure;

class ArtworkTest extends TestCase
{
    use CmsFixtureTrait;

    public function testAPlainAssetIsJustTheMediaInItsWrapper(): void
    {
        $html = (string)$this->createArtwork();

        self::assertStringStartsWith('<div', $html);
        self::assertStringContainsString('<img', $html);

        // nothing to caption and nothing to embed, so no figure is built
        self::assertStringNotContainsString('<figure', $html);
    }

    public function testTheCaptionBecomesAFigure(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->content = 'A caption';

        $html = (string)$this->createArtwork($asset);

        self::assertStringContainsString('<figure', $html);
        self::assertStringContainsString('<figcaption>A caption</figcaption>', $html);
    }

    /**
     * An asset's `content` is an `HtmlCustomAttribute`, sanitised by `HtmlValidator` on the way in, so the caption
     * is rendered as it stands. A project that redeclares it as plain text gets it escaped instead.
     */
    public function testAnHtmlCaptionIsRenderedAsItStands(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->content = '<strong>Bold</strong>';

        self::assertStringContainsString(
            '<figcaption><strong>Bold</strong></figcaption>',
            (string)$this->createArtwork($asset)
        );
    }

    public function testTheAssetsLinkWrapsTheMedia(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->link = 'https://example.test';
        $asset->name = 'The label';

        $html = (string)$this->createArtwork($asset);

        self::assertStringContainsString('href="https://example.test"', $html);
        self::assertStringContainsString('aria-label="The label"', $html);
    }

    /**
     * The first few artworks of a page are above the fold, so they load eagerly; everything after them is lazy.
     */
    public function testOnlyTheArtworksPastTheThresholdAreLazy(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');

        $eager = (string)$this->createArtwork($asset)->lazyLoadingPosition(2);
        self::assertStringNotContainsString('loading="lazy"', $eager);

        self::assertStringNotContainsString('loading="lazy"', (string)$this->createArtwork($asset)->lazyLoadingPosition(2));
        self::assertStringContainsString('loading="lazy"', (string)$this->createArtwork($asset)->lazyLoadingPosition(2));
    }

    /**
     * The counter belongs to the request, so `Bootstrap` drops it — a reset that only ran in the tests would
     * leave a resident application rendering every artwork after the first page lazily.
     */
    public function testTheCounterStartsAtZeroForEveryApplication(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');

        foreach (range(1, 10) as $ignored) {
            $this->createArtwork($asset)->lazyLoadingPosition(2)->__toString();
        }

        $this->reloadApplication();

        self::assertStringNotContainsString(
            'loading="lazy"',
            (string)$this->createArtwork($asset)->lazyLoadingPosition(2)
        );
    }

    /**
     * `false` hands the decision back to the media widget, which reads it off the asset, so the position on the
     * page stops mattering — every artwork renders the same way.
     */
    public function testTheCounterIsNotConsultedWhilePositionIsFalse(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $first = (string)$this->createArtwork($asset)->lazyLoadingPosition(false);

        foreach (range(1, 10) as $ignored) {
            self::assertSame($first, (string)$this->createArtwork($asset)->lazyLoadingPosition(false));
        }
    }

    public function testTheMaxWidthIsTakenFromTheFile(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');

        $html = (string)$this->createArtwork($asset)->maxWidth(true);

        self::assertStringContainsString("max-width: {$asset->file->width}px", $html);
        self::assertStringNotContainsString('max-width', (string)$this->createArtwork($asset));
    }

    public function testTheCallbacksCanReplaceEveryPart(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->content = 'A caption';

        $html = (string)$this->createArtwork($asset)
            ->wrapper(fn (Div $div) => $div->addClass('outer'))
            ->figure(fn (Figure $figure) => $figure->addClass('inner'))
            ->caption(fn (?Figcaption $caption) => $caption?->addClass('note'));

        self::assertStringContainsString('outer', $html);
        self::assertStringContainsString('inner', $html);
        self::assertStringContainsString('note', $html);
    }

    public function testTheCaptionCanBeDroppedByItsCallback(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');
        $asset->content = 'A caption';

        $html = (string)$this->createArtwork($asset)->caption(fn (): bool => false);

        self::assertStringNotContainsString('A caption', $html);
    }

    private function createArtwork(?Asset $asset = null): Artwork
    {
        return Artwork::make()
            ->asset($asset ?? $this->getAssetFromFixture('entry-asset'))
            ->adminLink(false);
    }
}
