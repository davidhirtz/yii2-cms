<?php
/**
 * EntryCategory.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryCategoryController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider $provider
 */

$this->setTitle(Yii::t('cms', 'Categories'));

use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryCategoryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
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