<?php
/**
 * Update entry.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm $entry
 */

use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\grid\AssetGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;

$this->setTitle(Yii::t('cms', 'Edit Entry'));
$this->setBreadcrumb(Yii::t('cms', 'Entries'), ['index']);
?>

<?= Submenu::widget([
    'model' => $entry,
]); ?>

<?= Html::errorSummary($entry); ?>


<?= Panel::widget([
    'title' => $this->title,
    'content' => EntryActiveForm::widget([
        'model' => $entry,
    ]),
]); ?>

<?php
if ($entry->getModule()->enableEntryAssets) {
    echo Panel::widget([
        'id' => 'assets',
        'title' => Yii::t('cms', 'Assets'),
        'content' => AssetGridView::widget([
            'parent' => $entry,
        ]),
    ]);
} ?>

<?= Panel::widget([
    'type' => 'danger',
    'title' => Yii::t('cms', 'Delete Entry'),
    'content' => DeleteActiveForm::widget([
        'model' => $entry,
        'message' => Yii::t('cms', 'Warning: Deleting this entry cannot be undone. All related sections will also be unrecoverably deleted. Please be certain!')
    ]),
]); ?>
