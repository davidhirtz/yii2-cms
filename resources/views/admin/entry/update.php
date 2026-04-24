<?php

declare(strict_types=1);

/**
 * @see EntryController::actionUpdate()
 *
 * @var View $this
 * @var Entry $entry
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryActionDropdown;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo EntryHeader::make()
    ->content(EntryActionDropdown::make()->model($entry))
    ->model($entry);

echo EntrySubmenu::make()
    ->model($entry);

echo FormContainer::make()
    ->form(EntryActiveForm::make()->model($entry));
