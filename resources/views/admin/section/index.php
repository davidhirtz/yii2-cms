<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\SectionController::actionIndex()
 *
 * @var View $this
 * @var SectionActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo EntryHeader::make()
    ->model($provider->entry);

echo EntrySubmenu::make()
    ->model($provider->entry);

echo GridContainer::make()
    ->grid(SectionGridView::make()
        ->provider($provider));
