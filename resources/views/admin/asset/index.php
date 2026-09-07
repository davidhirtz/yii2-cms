<?php

declare(strict_types=1);

/**
 * @see AssetController::actionIndex()
 *
 * @var View $this
 * @var AssetArrayDataProvider $provider
 * @var Entry|Section $parent
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetParentActionDropdown;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo AssetHeader::make()
    ->model($parent)
    ->content(AssetParentActionDropdown::make()
        ->provider($provider));

echo AssetSubmenu::make()
    ->model($parent);

echo GridContainer::make()
    ->grid(AssetGridView::make()
        ->provider($provider));
