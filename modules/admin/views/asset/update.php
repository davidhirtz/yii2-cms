<?php
/**
 * Update asset.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\AssetController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Asset $asset
 */

use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use \davidhirtz\yii2\cms\modules\admin\widgets\nav\AssetToolbar;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\AssetHelpPanel;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;

$this->setTitle(Yii::t('cms', 'Edit Asset'));
?>

<?= Submenu::widget([
    'model' => $asset->getParent(),
]); ?>

<?= AssetToolbar::widget([
    'model' => $asset,
]); ?>

<?php
if ($asset->section_id) {
    $this->setBreadcrumbs([
        Yii::t('cms', 'Sections') => ['/admin/section/index', 'entry' => $asset->entry_id],
        Yii::t('cms', 'Assets') => ['/admin/section/update', 'id' => $asset->section_id, '#' => 'assets'],
    ]);
} else {
    $this->setBreadcrumb(Yii::t('cms', 'Assets'), ['/admin/entry/update', 'id' => $asset->entry_id, '#' => 'assets']);
}
?>

<?= Html::errorSummary($asset); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => $asset->getActiveForm()::widget([
        'model' => $asset,
    ]),
]); ?>

<?= AssetHelpPanel::widget([
    'id' => 'operations',
    'model' => $asset,
]); ?>

<?= Panel::widget([
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
]); ?>

<?= Panel::widget([
    'type' => 'danger',
    'title' => Yii::t('media', 'Delete File'),
    'content' => DeleteActiveForm::widget([
        'model' => $asset->file,
        'action' => ['/admin/file/delete', 'id' => $asset->file_id],
        'message' => Yii::t('cms', 'Warning: Deleting this file cannot be undone. All related assets will also be unrecoverably deleted. Please be certain!')
    ]),
]); ?>