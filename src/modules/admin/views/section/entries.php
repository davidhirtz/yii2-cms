<?php
declare(strict_types=1);

/**
 * Section entries.
 * @see \Hirtz\Cms\modules\admin\controllers\SectionController::actionEntries()
 *
 * @var View $this
 * @var Section $section
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\models\Section;
use Hirtz\Cms\modules\admin\data\EntryActiveDataProvider;
use Hirtz\Cms\modules\admin\widgets\grids\SectionParentEntryGridView;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\bootstrap\Panel;

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
