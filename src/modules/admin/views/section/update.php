<?php

declare(strict_types=1);

/**
 * @see SectionController::actionUpdate()
 *
 * @var View $this
 * @var Section $section
 */

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\SectionController;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\SectionActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\AssetGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\SectionLinkedEntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\SectionHelpPanel;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;

$this->setTitle(Yii::t('cms', 'Edit Section'));
?>

<?= Submenu::widget([
    'model' => $section,
]); ?>

<?= Html::errorSummary($section); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => SectionActiveForm::widget([
        'model' => $section,
    ]),
]); ?>

<?php if ($section->hasAssetsEnabled()) {
    echo Panel::widget([
        'id' => 'assets',
        'title' => $section->getAttributeLabel('asset_count'),
        'content' => AssetGridView::widget([
            'parent' => $section,
        ]),
    ]);
} ?>

<?php if ($section->hasEntriesEnabled()) {
    echo Panel::widget([
        'id' => 'entries',
        'title' => Yii::t('cms', 'Linked entries'),
        'content' => SectionLinkedEntryGridView::widget([
            'section' => $section,
        ]),
    ]);
} ?>

<?= SectionHelpPanel::widget([
    'id' => 'operations',
    'model' => $section,
]); ?>

<?php if (Yii::$app->getUser()->can(Section::AUTH_SECTION_DELETE, ['section' => $section])) {
    echo Panel::widget([
        'id' => 'delete',
        'type' => 'danger',
        'title' => Yii::t('cms', 'Delete Section'),
        'content' => DeleteActiveForm::widget([
            'model' => $section,
        ]),
    ]);
} ?>
