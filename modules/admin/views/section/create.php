<?php
/**
 * Create section.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Section $section
 */

$this->setTitle(Yii::t('cms', 'Create New Section'));

use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel; ?>

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
