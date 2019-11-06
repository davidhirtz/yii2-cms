<?php
/**
 * Update category.
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\CategoryController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\cms\models\Category $category
 */

use davidhirtz\yii2\cms\modules\admin\widgets\nav\Submenu;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;

$this->setTitle(Yii::t('cms', 'Edit Category'));
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


<?= Panel::widget([
    'type' => 'danger',
    'title' => Yii::t('cms', 'Delete Category'),
    'content' => DeleteActiveForm::widget([
        'model' => $category,
        'message' => Yii::t('cms', 'Warning: Deleting this category cannot be undone. All related sections will also be unrecoverably deleted. All subcategories will also be unrecoverably deleted. Please be certain!')
    ]),
]); ?>
