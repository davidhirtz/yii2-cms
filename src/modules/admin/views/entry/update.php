<?php
declare(strict_types=1);

/**
 * @see EntryController::actionUpdate()
 *
 * @var View $this
 * @var Entry $entry
 */

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\AssetGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\EntryDeleteFrom;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\EntryPanel;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;

$this->title(Yii::t('cms', 'Edit Entry'));

echo CmsSubmenu::make()
    ->model($entry);

echo FormContainer::make()
    ->title($this->title)
    ->form(EntryActiveForm::make()
        ->model($entry));

if ($entry->hasAssetsEnabled()) {
    echo GridContainer::make()
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
