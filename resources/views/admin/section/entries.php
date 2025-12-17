<?php
declare(strict_types=1);

/**
 * Section entries.
 * @see \Hirtz\Cms\Modules\Admin\Controllers\SectionController::actionEntries()
 *
 * @var View $this
 * @var Section $section
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionParentEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Bootstrap\Panel;

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
