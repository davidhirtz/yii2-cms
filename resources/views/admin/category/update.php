<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\CategoryController::actionUpdate()
 *
 * @var View $this
 * @var Category $category
 */

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\CategoryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CategoryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CategorySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo CategoryHeader::make()
    ->model($category);

echo CategorySubmenu::make()
    ->model($category);

echo FormContainer::make()
    ->form(CategoryActiveForm::make()
        ->model($category));
