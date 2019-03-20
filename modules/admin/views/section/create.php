<?php
/**
 * Create section.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm $section
 */

$this->setTitle(Yii::t('cms', 'Create New Section'));
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
    'title' => $this->title,
    'content' => SectionActiveForm::widget([
        'model' => $section,
    ]),
]); ?>
