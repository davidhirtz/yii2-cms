<?php
/**
 * Update page.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\PageController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\PageForm $page
 */

$this->setTitle(Yii::t('cms', 'Edit Page'));
$this->setBreadcrumb(Yii::t('cms', 'Pages'), ['index']);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\PageActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\PageSubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm; ?>

<?= Html::errorSummary($page); ?>

<?= PageSubmenu::widget([
	'page' => $page,
]); ?>

<?= Panel::widget([
	'title'=>$this->title,
	'content'=>PageActiveForm::widget([
		'model'=>$page,
	]),
]); ?>

<?= Panel::widget([
	'type'=>'danger',
	'title'=>Yii::t('cms', 'Delete Page'),
	'content'=>DeleteActiveForm::widget([
		'model'=>$page,
	]),
]); ?>