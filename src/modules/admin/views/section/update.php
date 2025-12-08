<?php

declare(strict_types=1);

/**
 * @see SectionController::actionUpdate()
 *
 * @var View $this
 * @var Section $section
 */

use Hirtz\Cms\models\Section;
use Hirtz\Cms\modules\admin\controllers\SectionController;
use Hirtz\Cms\modules\admin\widgets\forms\SectionActiveForm;
use Hirtz\Cms\modules\admin\widgets\grids\AssetGridView;
use Hirtz\Cms\modules\admin\widgets\grids\SectionLinkedEntryGridView;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Cms\modules\admin\widgets\panels\SectionHelpPanel;
use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\bootstrap\Panel;
use Hirtz\Skeleton\widgets\forms\DeleteActiveForm;

$this->title(Yii::t('cms', 'Edit Section'));
?>

<?= CmsSubmenu::widget([
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
