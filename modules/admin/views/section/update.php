<?php
/**
 * Update section.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm $section
 */

$this->setTitle(Yii::t('cms', 'Update Section'));
$this->setBreadcrumb(Yii::t('cms', 'Entries'), ['entry/index']);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\SectionActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\EntrySubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm; ?>

<?= EntrySubmenu::widget([
	'entry' => $section->entry,
]); ?>

<?= Html::errorSummary($section); ?>

<?= Panel::widget([
	'title' => $this->title,
	'content' => SectionActiveForm::widget([
		'model' => $section,
	]),

]); ?>
<?= Panel::widget([
	'type'=>'danger',
	'title'=>Yii::t('cms', 'Delete Entry'),
	'content'=>DeleteActiveForm::widget([
		'model'=>$section,
	]),
]); ?>