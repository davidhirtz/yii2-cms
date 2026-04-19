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
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo EntryHeader::make()
    ->model($section);

echo EntrySubmenu::make()
    ->model($section);

$this->addBreadcrumb(Yii::t('cms', 'Move / Copy'));

echo GridContainer::make()
    ->grid(SectionParentEntryGridView::make()
        ->provider($provider)
        ->section($section));
