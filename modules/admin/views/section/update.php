<?php
/**
 * Update section.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm $section
 */

$this->setTitle(Yii::t('cms', 'Update Section'));
$this->setBreadcrumb(Yii::t('cms', 'Pages'), ['page/index']);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\SectionActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\PageSubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel; ?>

<?= Html::errorSummary($section); ?>

<?= PageSubmenu::widget([
	'page' => $section->page,
]); ?>

<?= Panel::widget([
	'title' => $section->getI18nAttribute('name'),
	'content' => SectionActiveForm::widget([
		'model' => $section,
	]),
]); ?>
