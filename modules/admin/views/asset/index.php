<?php
/**
 * Asset.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\AssetController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \yii\data\ActiveDataProvider $provider
 * @var EntryForm|\davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm $parent
 */

use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\media\modules\admin\widgets\grid\FileGridView;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('media', 'Assets'));

if ($parent instanceof \davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm) {
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