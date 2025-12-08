<?php

declare(strict_types=1);

/**
 * @var Asset[] $assets
 */

use Hirtz\Cms\Models\Asset;
use Hirtz\Media\widgets\Picture;

foreach ($assets as $asset) {
    echo Picture::widget(['asset' => $asset]);
}
