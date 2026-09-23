<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\Artwork;
use Hirtz\Cms\Widgets\Gallery;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Widgets\Media;

class GalleryTest extends TestCase
{
    use CmsFixtureTrait;

    public function testEveryAssetIsAnArtworkConfiguredByTheCaller(): void
    {
        $html = (string)$this->createGallery()
            ->artwork(fn (Artwork $artwork) => $artwork->addClass('artwork'))
            ->artwork(fn (Artwork $artwork) => $artwork->media(fn (Media $media) => $media->sizes('50vw')));

        self::assertStringStartsWith('<div class="artwork"', $html);
        self::assertStringContainsString('sizes="50vw"', $html);
    }

    public function testAViewFileTakesOverAndKeepsTheArtworkClosures(): void
    {
        $html = (string)$this->createGallery()
            ->viewFile('@cms/../tests/data/views/site/_gallery.php')
            ->viewParams(['class' => 'list'])
            ->artwork(fn (Artwork $artwork) => $artwork->addClass('artwork'));

        self::assertStringContainsString('<ul class="list">', $html);
        self::assertStringContainsString('<li><div class="artwork"', $html);
    }

    public function testTheContentClosureWinsOverTheViewFile(): void
    {
        $html = (string)$this->createGallery()
            ->viewFile('@cms/../tests/data/views/site/_gallery.php')
            ->artwork(fn (Artwork $artwork) => $artwork->addClass('artwork'))
            ->content(fn (array $assets, Gallery $gallery): string => implode('', array_map(
                fn (Asset $asset): string => '<p>' . $gallery->makeArtwork($asset) . '</p>',
                $assets
            )));

        self::assertStringStartsWith('<p><div class="artwork"', $html);
    }

    /**
     * @return Gallery<Asset>
     */
    private function createGallery(): Gallery
    {
        return Gallery::make()->assets([$this->getAssetFromFixture('entry-asset')]);
    }
}
