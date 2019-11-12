<?php
/**
 * Update entry.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Entry $entry
 */

use davidhirtz\yii2\cms\modules\admin\widgets\grid\AssetGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\base\EntryHelpPanel;

$this->setTitle(Yii::t('cms', 'Edit Entry'));
?>

<?= Submenu::widget([
    'model' => $entry,
]); ?>

<?= Html::errorSummary($entry); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => $entry->getActiveForm()::widget([
        'model' => $entry,
    ]),
]); ?>

<?php
if ($entry->hasAssetsEnabled()) {
    echo Panel::widget([
        'id' => 'assets',
        'title' => $entry->getAttributeLabel('asset_count'),
        'content' => AssetGridView::widget([
            'parent' => $entry,
        ]),
    ]);
} ?>

<?= EntryHelpPanel::widget([
    'id' => 'operations',
    'model' => $entry,
]); ?>

<?= Panel::widget([
    'type' => 'danger',
    'title' => Yii::t('cms', 'Delete Entry'),
    'content' => DeleteActiveForm::widget([
        'model' => $entry,
        'message' => Yii::t('cms', 'Warning: Deleting this entry cannot be undone. All related sections will also be unrecoverably deleted. Please be certain!')
    ]),
]); ?>
