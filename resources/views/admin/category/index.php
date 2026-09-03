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
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo CategoryHeader::make()
    ->provider($provider);

echo GridContainer::make()
    ->grid(CategoryGridView::make()
        ->provider($provider));
