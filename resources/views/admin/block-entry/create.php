<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockEntryController::actionCreate()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryRelationGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

/** @var Block $block */
$block = $provider->relatedModel;

echo BlockHeader::make()
    ->model($block);

echo BlockSubmenu::make()
    ->model($block);

echo GridContainer::make()
    ->grid(EntryRelationGridView::make()
        ->provider($provider));
