<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\Artwork;
use Hirtz\Cms\Widgets\Gallery;
use Hirtz\Media\Widgets\Media;

class GalleryTest extends TestCase
{
    use CmsFixtureTrait;

    public function testEveryAssetIsAnArtworkConfiguredByTheCaller(): void
    {
        $html = (string)Gallery::make()
            ->assets([$this->getAssetFromFixture('entry-asset')])
            ->viewFile('@cms/../resources/views/site/widgets/_assets.php')
            ->artwork(fn (Artwork $artwork) => $artwork->addClass('artwork'))
            ->artwork(fn (Artwork $artwork) => $artwork->media(fn (Media $media) => $media->sizes('50vw')));

        self::assertStringStartsWith('<div class="artwork"', $html);
        self::assertStringContainsString('sizes="50vw"', $html);
    }
}
