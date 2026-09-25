<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Widgets\Artwork;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Widgets\Media;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Figcaption;
use Hirtz\Skeleton\Html\Figure;
use Hirtz\Skeleton\Html\Img;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Override;

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
        $asset = $this->getAssetFromFixture('section-image-1');
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
        $asset = $this->getAssetFromFixture('section-image-1');
        $asset->content = '<strong>Bold</strong>';

        self::assertStringContainsString(
            '<figcaption><strong>Bold</strong></figcaption>',
            (string)$this->createArtwork($asset)
        );
    }

    public function testTheAssetsLinkWrapsTheMedia(): void
    {
        $asset = $this->getAssetFromFixture('section-image-1');
        $asset->link = 'https://example.test';
        $asset->name = 'The label';

        $html = (string)$this->createArtwork($asset);

        self::assertStringContainsString('href="https://example.test"', $html);
        self::assertStringContainsString('aria-label="The label"', $html);
    }

    /**
     * `getVisibleAttribute()` answers `null` for an attribute the asset does not declare, as a project asset
     * filtering its custom attributes does.
     */
    public function testAnAssetDeclaringNoLinkRendersWithoutOne(): void
    {
        $asset = $this->getAssetFromFixture('entry-asset');

        $linkless = new class () extends EntryAsset {
            #[Override]
            protected function getDefaultCustomAttributes(): array
            {
                return array_values(array_filter(
                    parent::getDefaultCustomAttributes(),
                    fn (CustomAttribute $attribute): bool => $attribute->name !== 'link'
                ));
            }
        };

        EntryAsset::populateRecord($linkless, $asset->getOldAttributes());
        $linkless->populateRelation('file', $asset->file);

        self::assertNull($linkless->getVisibleAttribute('link'));

        $html = (string)$this->createArtwork($linkless);

        self::assertStringContainsString('<img', $html);
        self::assertStringNotContainsString('<a', $html);
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

    public function testTheClosuresCanReplaceEveryPart(): void
    {
        $asset = $this->getAssetFromFixture('section-image-1');
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
        $asset = $this->getAssetFromFixture('section-image-1');
        $asset->content = 'A caption';

        $html = (string)$this->createArtwork($asset)->caption(fn (): null => null);

        self::assertStringNotContainsString('A caption', $html);
    }

    public function testTheClosuresStack(): void
    {
        $asset = $this->getAssetFromFixture('section-image-1');
        $asset->content = 'A caption';
        $asset->link = 'https://example.test';

        $html = (string)$this->createArtwork($asset)
            ->media(fn (Media $media) => $media->sizes('50vw'))
            ->media(fn (Media $media) => $media->image(fn (Img $img) => $img->addClass('image')))
            ->link(fn (?A $a) => $a?->addClass('first'))
            ->link(fn (?A $a) => $a?->addClass('second'))
            ->caption(fn (?Figcaption $caption) => $caption?->addClass('note'))
            ->caption(fn (?Figcaption $caption) => $caption?->addClass('small'));

        self::assertStringContainsString('sizes="50vw"', $html);
        self::assertStringContainsString('class="image"', $html);
        self::assertStringContainsString('class="first second"', $html);
        self::assertStringContainsString('class="note small"', $html);
    }

    /**
     * A subclass sets its media defaults in `makeMedia()`, which runs before the caller's closures.
     */
    public function testTheCallerOverridesTheDefaultsOfASubclass(): void
    {
        $artwork = new class () extends Artwork {
            #[Override]
            protected function makeMedia(): Media
            {
                return parent::makeMedia()->sizes('50vw');
            }
        };

        $asset = $this->getAssetFromFixture('entry-asset');

        self::assertStringContainsString('sizes="50vw"', (string)(clone $artwork)->asset($asset));

        $html = (string)$artwork->asset($asset)
            ->media(fn (Media $media) => $media->sizes('100vw'));

        self::assertStringContainsString('sizes="100vw"', $html);
        self::assertStringNotContainsString('50vw', $html);
    }

    private function createArtwork(?Asset $asset = null): Artwork
    {
        return Artwork::make()
            ->asset($asset ?? $this->getAssetFromFixture('entry-asset'))
            ->adminLink(false);
    }
}
