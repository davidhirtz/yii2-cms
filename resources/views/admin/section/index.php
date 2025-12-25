<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\SectionController::actionIndex()
 *
 * @var View $this
 * @var SectionActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('cms', 'Sections'));

echo CmsSubmenu::make()
    ->model($provider->entry);

echo GridContainer::make()
    ->grid(SectionGridView::make()
        ->provider($provider));
