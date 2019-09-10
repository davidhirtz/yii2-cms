<?php
/**
 * Asset.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\AssetController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \yii\data\ActiveDataProvider $provider
 * @var Entry|\davidhirtz\yii2\cms\models\Section $parent
 */

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\media\modules\admin\widgets\grid\FileGridView;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('media', 'Assets'));
$this->setBreadcrumb(Yii::t('cms', 'Entries'), ['/admin/entry/index']);

if ($parent instanceof \davidhirtz\yii2\cms\models\Section) {
    $this->setBreadcrumbs([
        Yii::t('cms', 'Sections') => ['/admin/section/index', 'entry' => $parent->entry_id],
        Yii::t('cms', 'Assets') => ['/admin/section/update', 'id' => $parent->id, '#' => 'assets'],
    ]);
} else {
    $this->setBreadcrumb(Yii::t('cms', 'Assets'), ['/admin/entry/update', 'id' => $parent->id, '#' => 'assets']);
}

?>

<?= Submenu::widget([
    'model' => $parent,
]); ?>

<?= Panel::widget([
    'content' => FileGridView::widget([
        'dataProvider' => $provider,
        'parent' => $parent,
    ]),
]); ?>