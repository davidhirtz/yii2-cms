<?php
declare(strict_types=1);

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
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\CategoryPanel;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;

$this->title(Yii::t('cms', 'Edit Category'));


echo CmsSubmenu::make()
    ->model($category);

echo FormContainer::make()
    ->title($this->title)
    ->form(CategoryActiveForm::make()
        ->model($category));

if ($category->getBranchCount()) {
    echo GridContainer::make()
        ->title(Yii::t('cms', 'Subcategories'))
        ->content(CategoryGridView::make()
            ->provider($provider));
}

echo CategoryPanel::make()
    ->model($category);

if (Yii::$app->getUser()->can(Category::AUTH_CATEGORY_DELETE, ['category' => $category])) {
    echo FormContainer::make()
        ->danger()
        ->title(Yii::t('cms', 'Delete Category'))
        ->form(DeleteActiveForm::make()
            ->model($category)
            ->message(Yii::t('cms', 'Warning: Deleting this category cannot be undone. All related sections will also be unrecoverably deleted. All subcategories will also be unrecoverably deleted. Please be certain!')));
}
