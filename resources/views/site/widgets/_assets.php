<?php

declare(strict_types=1);

/**
 * @see Gallery
 * @var Asset[] $assets
 */

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Widgets\Gallery;
use Hirtz\Media\widgets\Picture;

foreach ($assets as $asset) {
    echo Picture::make()
        ->asset($asset);
}
