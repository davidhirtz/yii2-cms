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
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\EntryPanel;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('cms', 'Edit Entry'));

echo CmsSubmenu::make()
    ->model($entry);

echo FormContainer::make()
    ->title($this->title)
    ->form(EntryActiveForm::make()
        ->model($entry));

if ($entry->hasAssetsEnabled()) {
    echo GridContainer::make()
        ->attribute('id', 'assets')
        ->title($entry->getAttributeLabel('asset_count'))
        ->grid(AssetGridView::make()
            ->parent($entry));
}

echo EntryPanel::make()
    ->model($entry);

if (Yii::$app->getUser()->can(Entry::AUTH_ENTRY_DELETE, ['entry' => $entry])) {
    echo EntryDeleteFrom::make()
        ->model($entry);
}
