<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryCategoryController::actionIndex()
 *
 * @var View $this
 * @var CategoryActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryCategoryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('cms', 'Categories'));

echo EntrySubmenu::make()
    ->model($provider->entry);

echo GridContainer::make()
    ->grid(EntryCategoryGridView::make()
        ->provider($provider));
