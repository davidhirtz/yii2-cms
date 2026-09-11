<?php

declare(strict_types=1);

/**
 * @see AssetController::actionIndex()
 *
 * @var View $this
 * @var Entry|Section $model
 * @var AssetArrayDataProvider $provider
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetSubmenu;
use Hirtz\Media\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetModelActionDropdown;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo AssetHeader::make()
    ->model($model)
    ->content(AssetModelActionDropdown::make()
        ->provider($provider));

echo AssetSubmenu::make()
    ->model($model);

echo GridContainer::make()
    ->grid(AssetGridView::make()
        ->provider($provider));
