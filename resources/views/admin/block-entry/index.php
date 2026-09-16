<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockEntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryRelationCreateButton;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\LinkedEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

/** @var Block $block */
$block = $provider->relatedModel;

echo BlockHeader::make()
    ->model($block)
    ->content(EntryRelationCreateButton::make()
        ->model($block));

echo BlockSubmenu::make()
    ->model($block);

echo GridContainer::make()
    ->grid(LinkedEntryGridView::make()
        ->provider($provider));
