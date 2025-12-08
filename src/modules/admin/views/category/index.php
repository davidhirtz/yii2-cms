<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\modules\admin\controllers\CategoryController::actionIndex()
 *
 * @var View $this
 * @var CategoryActiveDataProvider $provider
 */

use Hirtz\Cms\modules\admin\data\CategoryActiveDataProvider;
use Hirtz\Cms\modules\admin\widgets\grids\CategoryGridView;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;

$this->title(Yii::t('cms', 'Categories'));

echo CmsSubmenu::make()
    ->model($provider->category);

echo GridContainer::make()
    ->grid(CategoryGridView::make()
        ->provider($provider));
