<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\CategoryController::actionIndex()
 *
 * @var View $this
 * @var CategoryActiveDataProvider $provider
 */

use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\CategoryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;

$this->title(Yii::t('cms', 'Categories'));

echo CmsSubmenu::make()
    ->model($provider->category);

echo GridContainer::make()
    ->grid(CategoryGridView::make()
        ->provider($provider));
