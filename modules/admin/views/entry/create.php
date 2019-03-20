<?php
/**
 * Create entry.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm $entry
 */

$this->setTitle(Yii::t('cms', 'Create New Entry'));
$this->setBreadcrumb(Yii::t('cms', 'Entries'), ['index']);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\EntrySubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel; ?>

<?= EntrySubmenu::widget([
	'title'=>Html::a(Yii::t('cms', 'Entries'), ['index']),
]); ?>

<?= Html::errorSummary($entry); ?>

<?= Panel::widget([
	'title'=>$this->title,
	'content'=>EntryActiveForm::widget([
		'model'=>$entry,
	]),
]); ?>
