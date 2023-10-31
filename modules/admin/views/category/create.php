<?php
/**
 * Create category.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\CategoryController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Category $category
 */

use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('cms', 'Create New Category'));
?>

<?= Submenu::widget([
    'model' => $category,
]); ?>

<?= Html::errorSummary($category); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => $category->getActiveForm()::widget([
        'model' => $category,
    ]),
]); ?>
