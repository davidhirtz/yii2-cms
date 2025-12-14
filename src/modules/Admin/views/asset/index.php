<?php
declare(strict_types=1);

/**
 * @see AssetController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var Entry|Section $parent
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\CmsSubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Bootstrap\Panel;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('media', 'Assets'));
?>

<?= CmsSubmenu::widget([
    'model' => $parent,
]); ?>

<?php $this->setBreadcrumb(Yii::t('media', 'Assets'), [$parent instanceof Section ? '/admin/section/update' : '/admin/entry/update', 'id' => $parent->id, '#' => 'assets']); ?>

<?= Panel::widget([
    'content' => FileGridView::widget([
        'dataProvider' => $provider,
        'parent' => $parent,
    ]),
]); ?>
