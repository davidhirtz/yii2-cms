<?php
/**
 * Create page.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\PageController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\PageForm $page
 */

$this->setTitle(Yii::t('cms', 'Create New Page'));
$this->setBreadcrumb(Yii::t('cms', 'Pages'), ['index']);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\PageActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\PageSubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel; ?>

<?= Html::errorSummary($page); ?>

<?= PageSubmenu::widget([
	'title'=>Html::a(Yii::t('cms', 'Pages'), ['index']),
]); ?>

<?= Panel::widget([
	'title'=>$this->title,
	'content'=>PageActiveForm::widget([
		'model'=>$page,
	]),
]); ?>
