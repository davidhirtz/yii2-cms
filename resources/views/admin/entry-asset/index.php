<?php

declare(strict_types=1);

/**
 * @see EntryAssetController::actionIndex()
 *
 * @var View $this
 * @var Entry $model
 * @var AssetArrayDataProvider $provider
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Media\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetModelActionDropdown;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo EntryHeader::make()
    ->model($model)
    ->content(AssetModelActionDropdown::make()
        ->provider($provider));

echo EntrySubmenu::make()
    ->model($model);

echo GridContainer::make()
    ->grid(AssetGridView::make()
        ->provider($provider));
