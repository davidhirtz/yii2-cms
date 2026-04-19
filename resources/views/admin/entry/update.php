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
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryDeleteFrom;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\EntryPanel;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo EntryHeader::make()
    ->model($entry);

echo EntrySubmenu::make()
    ->model($entry);

echo FormContainer::make()
    ->title(Yii::t('cms', 'Edit Entry'))
    ->form(EntryActiveForm::make()
        ->model($entry));

echo GridContainer::make()
    ->attribute('id', 'assets')
    ->attribute('hidden', !$entry->hasAssetsEnabled())
    ->title($entry->getAttributeLabel('asset_count'))
    ->grid(AssetGridView::make()
        ->parent($entry));

echo EntryPanel::make()
    ->model($entry);

if (Yii::$app->getUser()->can(Entry::AUTH_ENTRY_DELETE, ['entry' => $entry])) {
    echo EntryDeleteFrom::make()
        ->entry($entry);
}
