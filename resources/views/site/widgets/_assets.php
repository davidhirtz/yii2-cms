<?php

declare(strict_types=1);

/**
 * @see Gallery::renderAssetsInternal()
 * @var Asset[] $assets
 * @var Gallery<Asset> $gallery
 */

use Hirtz\Cms\Widgets\Gallery;
use Hirtz\Media\Models\Asset;

foreach ($assets as $asset) {
    echo $gallery->makeArtwork($asset);
}
