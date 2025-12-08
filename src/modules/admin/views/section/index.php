<?php
declare(strict_types=1);

/**
 * Sections.
 * @see \Hirtz\Cms\modules\admin\controllers\SectionController::actionIndex()
 *
 * @var \Hirtz\Skeleton\web\View $this
 * @var \Hirtz\Cms\models\Entry $entry
 */

$this->title(Yii::t('cms', 'Sections'));

use Hirtz\Cms\modules\admin\widgets\grids\SectionGridView;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\widgets\bootstrap\Panel;

?>

<?= CmsSubmenu::widget([
    'model' => $entry,
]); ?>

<?= Panel::widget([
    'content' => SectionGridView::widget([
        'entry' => $entry,
    ]),
]); ?>
