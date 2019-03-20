<?php
/**
 * Create section.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm $section
 */

$this->setTitle(Yii::t('cms', 'Create New Section'));
$this->setBreadcrumb(Yii::t('cms', 'Entries'), ['entry/index']);

use davidhirtz\yii2\cms\modules\admin\widgets\forms\SectionActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\EntrySubmenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel; ?>

<?= EntrySubmenu::widget([
    'entry' => $section->entry,
]); ?>

<?= Html::errorSummary($section); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => SectionActiveForm::widget([
        'model' => $section,
    ]),
]); ?>
