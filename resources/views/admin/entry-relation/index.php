<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryRelationController::actionIndex()
 *
 * @var View $this
 * @var Entry $entry
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryRelationParentGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('cms', 'ENTRY_RELATION_PARENT_PLURAL'));

echo EntryHeader::make()
    ->model($entry);

echo EntrySubmenu::make()
    ->model($entry);

echo GridContainer::make()
    ->grid(EntryRelationParentGridView::make()
        ->entry($entry));
