<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo EntryHeader::make()
    ->provider($provider);

if ($provider->parent) {
    echo EntrySubmenu::make()
        ->model($provider->parent);
}

echo GridContainer::make()
    ->grid(EntryGridView::make()
        ->provider($provider));
