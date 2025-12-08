<?php

declare(strict_types=1);

/**
 * @see EntryController::actionUpdate()
 *
 * @var View $this
 * @var Entry $entry
 */

use Hirtz\Cms\models\Entry;
use Hirtz\Cms\modules\admin\controllers\EntryController;
use Hirtz\Cms\modules\admin\widgets\forms\EntryActiveForm;
use Hirtz\Cms\modules\admin\widgets\grids\AssetGridView;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Cms\modules\admin\widgets\panels\EntryDeleteFrom;
use Hirtz\Cms\modules\admin\widgets\panels\EntryPanel;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\forms\FormContainer;
use Hirtz\Skeleton\widgets\grids\GridContainer;

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
