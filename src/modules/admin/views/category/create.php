<?php

declare(strict_types=1);

/**
 * @see CategoryController::actionCreate()
 *
 * @var View $this
 * @var Category $category
 */

use Hirtz\Cms\models\Category;
use Hirtz\Cms\modules\admin\controllers\CategoryController;
use Hirtz\Cms\modules\admin\widgets\forms\CategoryActiveForm;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\forms\FormContainer;

$this->title(Yii::t('cms', 'Create New Category'));

echo CmsSubmenu::make()
    ->model($category);

echo FormContainer::make()
    ->title($this->title)
    ->form(CategoryActiveForm::make()
        ->model($category));
