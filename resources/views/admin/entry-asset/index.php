<?php

declare(strict_types=1);

/**
 * @see EntryAssetController::actionIndex()
 *
 * @var View $this
 * @var AssetArrayDataProvider $provider
 * @var Entry $entry
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Cms\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetParentActionDropdown;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo EntryHeader::make()
    ->model($entry)
    ->content(AssetParentActionDropdown::make()
        ->provider($provider));

echo EntrySubmenu::make()
    ->model($entry);

echo GridContainer::make()
    ->grid(AssetGridView::make()
        ->provider($provider));
