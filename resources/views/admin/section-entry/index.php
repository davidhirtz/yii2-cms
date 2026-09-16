<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\SectionEntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryRelationCreateButton;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\LinkedEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

/** @var Section $section */
$section = $provider->relatedModel;

echo SectionHeader::make()
    ->model($section)
    ->content(EntryRelationCreateButton::make()
        ->model($section));

echo SectionSubmenu::make()
    ->model($section);

echo GridContainer::make()
    ->grid(LinkedEntryGridView::make()
        ->provider($provider));
