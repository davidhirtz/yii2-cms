<?php
/**
 * Update section.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Section $section
 */

use davidhirtz\yii2\cms\modules\admin\widgets\grid\AssetGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\SectionHelpPanel;

$this->setTitle(Yii::t('cms', 'Edit Section'));
?>

<?= Submenu::widget([
    'model' => $section->entry,
]); ?>

<?= Html::errorSummary($section); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => $section->getActiveForm()::widget([
        'model' => $section,
    ]),

]); ?>

<?php
if ($section->getModule()->enableSectionAssets) {
    echo Panel::widget([
        'id' => 'assets',
        'title' => $section->getAttributeLabel('asset_count'),
        'content' => AssetGridView::widget([
            'parent' => $section,
        ]),
    ]);
}
?>

<?= SectionHelpPanel::widget([
    'id' => 'operations',
    'model' => $section,
]); ?>

<?= Panel::widget([
    'id' => 'delete',
    'type' => 'danger',
    'title' => Yii::t('cms', 'Delete Section'),
    'content' => DeleteActiveForm::widget([
        'model' => $section,
    ]),
]); ?>
