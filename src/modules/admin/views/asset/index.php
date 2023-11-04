<?php
/**
 * @see AssetController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var Entry|Section $parent
 */

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\AssetController;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
use davidhirtz\yii2\media\modules\admin\widgets\grids\FileGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('media', 'Assets'));
?>

<?= Submenu::widget([
    'model' => $parent,
]); ?>

<?php $this->setBreadcrumb(Yii::t('cms', 'Assets'), [$parent instanceof Section ? '/admin/section/update' : '/admin/entry/update', 'id' => $parent->id, '#' => 'assets']); ?>

<?= Panel::widget([
    'content' => FileGridView::widget([
        'dataProvider' => $provider,
        'parent' => $parent,
    ]),
]); ?>