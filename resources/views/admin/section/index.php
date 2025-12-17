<?php
declare(strict_types=1);

/**
 * Sections.
 * @see \Hirtz\Cms\Modules\Admin\Controllers\SectionController::actionIndex()
 *
 * @var \Hirtz\Skeleton\Web\View $this
 * @var \Hirtz\Cms\Models\Entry $entry
 */

$this->title(Yii::t('cms', 'Sections'));

use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Skeleton\Widgets\Bootstrap\Panel;

?>

<?= CmsSubmenu::widget([
    'model' => $entry,
]); ?>

<?= Panel::widget([
    'content' => SectionGridView::widget([
        'entry' => $entry,
    ]),
]); ?>
