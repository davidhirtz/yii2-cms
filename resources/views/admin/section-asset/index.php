<?php

declare(strict_types=1);

/**
 * @see SectionAssetController::actionIndex()
 *
 * @var View $this
 * @var Section $model
 * @var AssetArrayDataProvider $provider
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Media\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetModelActionDropdown;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo SectionHeader::make()
    ->model($model)
    ->content(AssetModelActionDropdown::make()
        ->provider($provider));

echo SectionSubmenu::make()
    ->model($model);

echo GridContainer::make()
    ->grid(AssetGridView::make()
        ->provider($provider));
