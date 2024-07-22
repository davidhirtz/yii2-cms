<?php
/**
 * Update category
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\CategoryController::actionUpdate()
 *
 * @var View $this
 * @var CategoryActiveDataProvider $provider
 * @var Category $category
 */

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\CategoryActiveForm;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\CategoryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\Submenu;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\CategoryHelpPanel;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\web\View;
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
    'content' => CategoryActiveForm::widget([
        'model' => $category,
    ]),
]); ?>

<?php if ($category->getBranchCount()) {
    echo Panel::widget([
        'title' => Yii::t('cms', 'Subcategories'),
        'content' => CategoryGridView::widget([
            'dataProvider' => $provider,
            'layout' => '{items}',
        ]),
    ]);
}
?>

<?= CategoryHelpPanel::widget([
    'id' => 'operations',
    'model' => $category,
]); ?>

<?php if (Yii::$app->getUser()->can(Category::AUTH_CATEGORY_DELETE, ['category' => $category])) {
    echo Panel::widget([
        'type' => 'danger',
        'title' => Yii::t('cms', 'Delete Category'),
        'content' => DeleteActiveForm::widget([
            'model' => $category,
            'message' => Yii::t('cms', 'Warning: Deleting this category cannot be undone. All related sections will also be unrecoverably deleted. All subcategories will also be unrecoverably deleted. Please be certain!')
        ]),
    ]);
} ?>
