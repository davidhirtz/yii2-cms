<?php
declare(strict_types=1);

/**
 * @see AssetController::actionUpdate()
 *
 * @var View $this
 * @var Asset $asset
 */

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\AssetController;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\AssetActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\AssetHelpPanel;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;

$this->setTitle(Yii::t('cms', 'Edit Asset'));
?>

<?= Submenu::widget([
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
