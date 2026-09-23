<?php

declare(strict_types=1);

/**
 * @var Asset[] $assets
 * @var Gallery<Asset> $gallery
 * @var string $class
 */

use Hirtz\Cms\Widgets\Gallery;
use Hirtz\Media\Models\Asset;

?>
<ul class="<?= $class; ?>">
    <?php foreach ($assets as $asset) { ?>
        <li><?= $gallery->makeArtwork($asset); ?></li>
    <?php } ?>
</ul>
