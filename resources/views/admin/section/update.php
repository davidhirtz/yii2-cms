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
use Hirtz\Cms\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionLinkedEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionActionDropdown;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo EntryHeader::make()
    ->model($section)
    ->content(SectionActionDropdown::make()
        ->model($section));

echo EntrySubmenu::make()
    ->model($section->entry);

echo FormContainer::make()
    ->title($this->title)
    ->form(SectionActiveForm::make()
        ->model($section));

if ($section::getModule()->enableSectionAssets) {
    echo GridContainer::make()
        ->attribute('id', 'assets')
        ->attribute('hidden', !$section->hasAssetsEnabled())
        ->title($section->getAttributeLabel('asset_count'))
        ->grid(AssetGridView::make()
            ->parent($section));
}

if ($section::getModule()->enableSectionEntries) {
    echo GridContainer::make()
        ->attribute('id', 'entries')
        ->attribute('hidden', !$section->hasEntriesEnabled())
        ->title(Yii::t('cms', 'Linked entries'))
        ->grid(SectionLinkedEntryGridView::make()
            ->section($section));
}
