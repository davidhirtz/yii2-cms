<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\SectionController::actionEntries()
 *
 * @var View $this
 * @var Section $section
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionParentEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo SectionHeader::make()
    ->model($section);

echo SectionSubmenu::make()
    ->model($section);

echo GridContainer::make()
    ->grid(SectionParentEntryGridView::make()
        ->provider($provider)
        ->section($section));
