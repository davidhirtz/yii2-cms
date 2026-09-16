<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockController::actionIndex()
 *
 * @var View $this
 * @var BlockActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\BlockActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\BlockGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo BlockHeader::make()
    ->provider($provider);

echo GridContainer::make()
    ->grid(BlockGridView::make()
        ->provider($provider));
