<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\CategoryController::actionIndex()
 *
 * @var View $this
 * @var CategoryActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\CategoryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CategoryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CategorySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo CategoryHeader::make()
    ->provider($provider);

if ($provider->category) {
    echo CategorySubmenu::make()
        ->model($provider->category);
}

echo GridContainer::make()
    ->grid(CategoryGridView::make()
        ->provider($provider));
