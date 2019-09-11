<?php
/**
 * EntryCategory.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryCategoryController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider $provider
 */

$this->setTitle(Yii::t('cms', 'Categories'));
$this->setBreadcrumb(Yii::t('cms', 'Entries'), ['entry/index']);

use davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryCategoryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

?>

<?= Submenu::widget([
    'model' => $provider->entry,
]); ?>

<?= Panel::widget([
    'content' => EntryCategoryGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>