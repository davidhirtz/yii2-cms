<?php
/**
 * Asset.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\AssetController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \yii\data\ActiveDataProvider $provider
 * @var Entry|Section $parent
 */

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\media\modules\admin\widgets\grid\FileGridView;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

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