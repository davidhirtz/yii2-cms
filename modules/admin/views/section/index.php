<?php
/**
 * Sections.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm $entry
 */

$this->setTitle(Yii::t('cms', 'Sections'));
$this->setBreadcrumb(Yii::t('cms', 'Entries'), ['entry/index']);

use davidhirtz\yii2\cms\modules\admin\widgets\grid\SectionGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\EntrySubmenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
?>

<?= EntrySubmenu::widget([
	'entry' => $entry,
]); ?>

<?= Panel::widget([
	'content' => SectionGridView::widget([
		'entry' => $entry,
	]),
]); ?>