<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockController::actionSections()
 *
 * @var View $this
 * @var Block $block
 */

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\BlockSectionGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo BlockHeader::make()
    ->model($block);

echo BlockSubmenu::make()
    ->model($block);

echo GridContainer::make()
    ->grid(BlockSectionGridView::make()
        ->block($block));
