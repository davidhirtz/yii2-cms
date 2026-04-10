<?php

declare(strict_types=1);

/**
 * @see AssetController::actionUpdate()
 *
 * @var View $this
 * @var Asset $asset
 */

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\AssetActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\AssetPanel;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\DeleteActiveForm;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

$this->title(Yii::t('cms', 'Edit Asset'));

echo CmsSubmenu::make()
    ->model($asset);

echo FormContainer::make()
    ->title($this->title)
    ->form(AssetActiveForm::make()
        ->model($asset));

echo AssetPanel::make()
    ->model($asset);

$permission = $asset->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_UPDATE : Section::AUTH_SECTION_ASSET_UPDATE;

if (Yii::$app->getUser()->can($permission, ['asset' => $asset])) {
    echo FormContainer::make()
        ->danger()
        ->title(Yii::t('cms', 'Remove Asset'))
        ->form(DeleteActiveForm::make()
            ->model($asset)
            ->message(Yii::t('cms', 'Notice: Removing an asset will not delete the actual file.')));

    // Todo:
    //                'buttons' => Html::button(Yii::t('cms', 'Remove'), [
    //                'class' => 'btn btn-danger',
    //                'data-method' => 'post',
    //                'data-message' => Yii::t('cms', 'Are you sure you want to remove this asset?'),
    //                'type' => 'submit',
    //            ]),

}


if (Yii::$app->getUser()->can(File::AUTH_FILE_DELETE, ['file' => $asset->file])) {
    echo FormContainer::make()
        ->danger()
        ->title(Yii::t('media', 'Delete File'))
        ->form(DeleteActiveForm::make()
            ->model($asset->file)
            ->action(['/admin/media/file/delete', 'id' => $asset->file_id])
            ->message(Yii::t('cms', 'Warning: Deleting this file cannot be undone. All related assets will also be unrecoverably deleted. Please be certain!')));
}
