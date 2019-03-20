<?php
/**
 * Sections.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\PageForm $page
 */

$this->setTitle(Yii::t('cms', 'Sections'));
$this->setBreadcrumb(Yii::t('cms', 'Pages'), ['page/index']);

use davidhirtz\yii2\cms\modules\admin\widgets\grid\SectionGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\PageSubmenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
?>

<?= PageSubmenu::widget([
	'page' => $page,
]); ?>

<?= Panel::widget([
	'content' => SectionGridView::widget([
		'page' => $page,
	]),
]); ?>