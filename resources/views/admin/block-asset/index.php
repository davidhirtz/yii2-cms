<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockAssetController::actionIndex()
 *
 * @var View $this
 * @var Block $model
 * @var AssetArrayDataProvider $provider
 */

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Media\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetModelActionDropdown;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo BlockHeader::make()
    ->model($model)
    ->content(AssetModelActionDropdown::make()
        ->provider($provider));

echo BlockSubmenu::make()
    ->model($model);

echo GridContainer::make()
    ->grid(AssetGridView::make()
        ->provider($provider));
