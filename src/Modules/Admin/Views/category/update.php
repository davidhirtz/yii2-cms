<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\CategoryController::actionUpdate()
 *
 * @var View $this
 * @var CategoryActiveDataProvider $provider
 * @var Category $category
 */

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\CategoryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\CategoryParentGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\CmsSubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\CategoryPanel;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\DeleteActiveForm;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

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
        ->grid(CategoryParentGridView::make()
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
