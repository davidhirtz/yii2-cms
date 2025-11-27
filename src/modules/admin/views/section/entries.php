<?php
declare(strict_types=1);

/**
 * Section entries.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\SectionController::actionEntries()
 *
 * @var View $this
 * @var Section $section
 * @var EntryActiveDataProvider $provider
 */

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\SectionParentEntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->title(Yii::t('cms', 'Edit Section'));
?>

<?= CmsSubmenu::widget([
    'model' => $section,
]); ?>

<?php $this->setBreadcrumb(Yii::t('cms', 'Move / Copy')); ?>

<?= Panel::widget([
    'content' => SectionParentEntryGridView::widget([
        'dataProvider' => $provider,
        'section' => $section,
    ]),
]); ?>
