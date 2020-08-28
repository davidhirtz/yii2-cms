<?php
/**
 * Section entries.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionEntries()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Section $section
 * @var \davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider $provider
 */

use davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Edit Section'));
?>

<?= Submenu::widget([
    'model' => $section,
]); ?>

<?php $this->setBreadcrumb(Yii::t('cms', 'Move / Copy')); ?>

<?= Panel::widget([
    'content' => EntryGridView::widget([
        'dataProvider' => $provider,
        'section' => $section,
    ]),
]); ?>