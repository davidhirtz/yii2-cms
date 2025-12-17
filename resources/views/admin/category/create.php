<?php

declare(strict_types=1);

/**
 * @see CategoryController::actionCreate()
 *
 * @var View $this
 * @var Category $category
 */

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Controllers\CategoryController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\CategoryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

$this->title(Yii::t('cms', 'Create New Category'));

echo CmsSubmenu::make()
    ->model($category);

echo FormContainer::make()
    ->title($this->title)
    ->form(CategoryActiveForm::make()
        ->model($category));
