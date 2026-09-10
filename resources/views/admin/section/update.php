<?php

declare(strict_types=1);

/**
 * @see SectionController::actionUpdate()
 *
 * @var View $this
 * @var Section $section
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionActionDropdown;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo SectionHeader::make()
    ->model($section)
    ->content(SectionActionDropdown::make()
        ->model($section));

echo SectionSubmenu::make()
    ->model($section);

echo FormContainer::make()
    ->form(SectionActiveForm::make()
        ->model($section));
