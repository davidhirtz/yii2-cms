<?php
declare(strict_types=1);

/**
 * EntryCategory.
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryCategoryController::actionIndex()
 *
 * @var \Hirtz\Skeleton\Web\View $this
 * @var \Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider $provider
 */

$this->title(Yii::t('cms', 'Categories'));

use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryCategoryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Skeleton\Widgets\Bootstrap\Panel;

?>

<?= CmsSubmenu::widget([
    'model' => $provider->entry,
]); ?>

<?= Panel::widget([
    'content' => EntryCategoryGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>
