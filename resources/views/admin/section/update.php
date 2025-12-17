<?php

declare(strict_types=1);

/**
 * @see SectionController::actionUpdate()
 *
 * @var View $this
 * @var Section $section
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\AssetGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionLinkedEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\SectionHelpPanel;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Bootstrap\Panel;
use Hirtz\Skeleton\Widgets\Forms\DeleteActiveForm;

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
