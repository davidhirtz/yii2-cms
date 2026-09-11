<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Media\Modules\Admin\Controllers\AbstractAssetController;

/**
 * The views come from the cms admin module, which is what the controller is mounted under.
 */
class AssetController extends AbstractAssetController
{
    protected array $assetClasses = [
        EntryAsset::class,
        SectionAsset::class,
    ];
}
