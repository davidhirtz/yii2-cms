<?php
/**
 * Update entry.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm $entry
 */

$this->setTitle(Yii::t('cms', 'Edit Entry'));
$this->setBreadcrumb(Yii::t('cms', 'Entries'), ['index']);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\EntrySubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm; ?>

<?= EntrySubmenu::widget([
	'entry' => $entry,
]); ?>

<?= Html::errorSummary($entry); ?>


<?= Panel::widget([
	'title'=>$this->title,
	'content'=>EntryActiveForm::widget([
		'model'=>$entry,
	]),
]); ?>

<?= Panel::widget([
	'type'=>'danger',
	'title'=>Yii::t('cms', 'Delete Entry'),
	'content'=>DeleteActiveForm::widget([
		'model'=>$entry,
	]),
]); ?>