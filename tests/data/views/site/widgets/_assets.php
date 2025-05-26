<?php

declare(strict_types=1);

/**
 * @var Asset[] $assets
 */

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\widgets\Picture;

foreach ($assets as $asset) {
    echo Picture::widget(['asset' => $asset]);
}
