<?php

declare(strict_types=1);

/**
 * @see SectionController::actionCreate()
 *
 * @var View $this
 * @var Section $section
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo EntryHeader::make()
    ->model($section);

echo EntrySubmenu::make()
    ->model($section);

echo FormContainer::make()
    ->title($this->title)
    ->form(SectionActiveForm::make()
        ->model($section));
