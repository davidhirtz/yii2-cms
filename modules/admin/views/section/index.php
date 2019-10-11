<?php
/**
 * Sections.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Entry $entry
 */

$this->setTitle(Yii::t('cms', 'Sections'));

use davidhirtz\yii2\cms\modules\admin\widgets\grid\SectionGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
?>

<?= Submenu::widget([
	'model' => $entry,
]); ?>

<?= Panel::widget([
	'content' => SectionGridView::widget([
		'entry' => $entry,
	]),
]); ?>