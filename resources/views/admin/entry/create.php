<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryController::actionCreate()
 *
 * @var View $this
 * @var Entry $entry
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo EntryHeader::make()
    ->model($section);

echo EntrySubmenu::make()
    ->title(Yii::t('cms', 'New Entry'));

echo FormContainer::make()
    ->title($this->title)
    ->form(EntryActiveForm::make()
        ->model($entry));
