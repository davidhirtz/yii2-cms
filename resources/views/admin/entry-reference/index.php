<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryReferenceController::actionIndex()
 *
 * @var View $this
 * @var Entry $entry
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryReferenceGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('cms', 'ENTRY_REFERENCE_PLURAL'));

echo EntryHeader::make()
    ->model($entry);

echo EntrySubmenu::make()
    ->model($entry);

echo GridContainer::make()
    ->grid(EntryReferenceGridView::make()
        ->entry($entry));
