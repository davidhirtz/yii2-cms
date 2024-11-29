<?php
declare(strict_types=1);

/**
 * Categories.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\CategoryController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider $provider
 */

use davidhirtz\yii2\cms\modules\admin\widgets\grids\CategoryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Categories'));
?>

<?= Submenu::widget([
    'model' => $provider->category,
]); ?>

<?= Panel::widget([
    'content' => CategoryGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>
