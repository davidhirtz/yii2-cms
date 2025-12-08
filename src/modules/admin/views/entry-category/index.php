<?php
declare(strict_types=1);

/**
 * EntryCategory.
 * @see \Hirtz\Cms\modules\admin\controllers\EntryCategoryController::actionIndex()
 *
 * @var \Hirtz\Skeleton\web\View $this
 * @var \Hirtz\Cms\modules\admin\data\CategoryActiveDataProvider $provider
 */

$this->title(Yii::t('cms', 'Categories'));

use Hirtz\Cms\modules\admin\widgets\grids\EntryCategoryGridView;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\widgets\bootstrap\Panel;

?>

<?= CmsSubmenu::widget([
    'model' => $provider->entry,
]); ?>

<?= Panel::widget([
    'content' => EntryCategoryGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>
