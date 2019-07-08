<?php
/**
 * Create section.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm $section
 */

$this->setTitle(Yii::t('cms', 'Create New Section'));
$this->setBreadcrumbs([
    Yii::t('cms', 'Entries') => ['entry/index'],
    Yii::t('cms', 'Sections') => ['index', 'entry' => $section->entry_id],
]);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\SectionActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel; ?>

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
