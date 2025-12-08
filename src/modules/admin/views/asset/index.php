<?php
declare(strict_types=1);

/**
 * @see AssetController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var Entry|Section $parent
 */

use Hirtz\Cms\models\Entry;
use Hirtz\Cms\models\Section;
use Hirtz\Cms\modules\admin\controllers\AssetController;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Media\modules\admin\widgets\grids\FileGridView;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\bootstrap\Panel;
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
