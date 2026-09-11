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
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CategoryHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo CategoryHeader::make()
    ->title(Yii::t('cms', 'CATEGORY_CREATE_TITLE'));

echo FormContainer::make()
    ->form(CategoryActiveForm::make()
        ->model($category));
