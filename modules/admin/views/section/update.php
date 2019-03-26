<?php
/**
 * Update section.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm $section
 */

$this->setTitle(Yii::t('cms', 'Update Section'));
$this->setBreadcrumb(Yii::t('cms', 'Sections'), ['entry/update', $section->entry_id]);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\SectionActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\grid\AssetGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm; ?>

<?= Submenu::widget([
    'model' => $section->entry,
]); ?>

<?= Html::errorSummary($section); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => SectionActiveForm::widget([
        'model' => $section,
    ]),

]); ?>

<?php
if ($section->getModule()->enableSectionAssets) {
    echo Panel::widget([
        'id' => 'assets',
        'title' => Yii::t('cms', 'Assets'),
        'content' => AssetGridView::widget([
            'parent' => $section,
        ]),
    ]);
}
?>

<?= Panel::widget([
    'type' => 'danger',
    'title' => Yii::t('cms', 'Delete Entry'),
    'content' => DeleteActiveForm::widget([
        'model' => $section,
    ]),
]); ?>