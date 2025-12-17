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
use Hirtz\Cms\Modules\Admin\Widgets\Forms\CmsSubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\AssetHelpPanel;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Bootstrap\Panel;
use Hirtz\Skeleton\Widgets\Forms\DeleteActiveForm;

$this->title(Yii::t('cms', 'Edit Asset'));
?>

<?= CmsSubmenu::widget([
    'model' => $asset,
]); ?>

<?= Html::errorSummary($asset); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => AssetActiveForm::widget([
        'model' => $asset,
    ]),
]); ?>

<?= AssetHelpPanel::widget([
    'id' => 'operations',
    'model' => $asset,
]); ?>

<?php if (Yii::$app->getUser()->can($asset->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_DELETE : Section::AUTH_SECTION_ASSET_DELETE, ['asset' => $asset])) {
    echo Panel::widget([
        'type' => 'danger',
        'title' => Yii::t('cms', 'Remove Asset'),
        'content' => DeleteActiveForm::widget([
            'model' => $asset,
            'buttons' => Html::button(Yii::t('cms', 'Remove'), [
                'class' => 'btn btn-danger',
                'data-method' => 'post',
                'data-message' => Yii::t('cms', 'Are you sure you want to remove this asset?'),
                'type' => 'submit',
            ]),
            'message' => Yii::t('cms', 'Notice: Removing an asset will not delete the actual file.')
        ]),
    ]);
} ?>

<?php if (Yii::$app->getUser()->can('fileDelete', ['file' => $asset->file])) {
    echo Panel::widget([
        'type' => 'danger',
        'title' => Yii::t('media', 'Delete File'),
        'content' => DeleteActiveForm::widget([
            'model' => $asset->file,
            'action' => ['/admin/file/delete', 'id' => $asset->file_id],
            'message' => Yii::t('cms', 'Warning: Deleting this file cannot be undone. All related assets will also be unrecoverably deleted. Please be certain!')
        ]),
    ]);
} ?>
