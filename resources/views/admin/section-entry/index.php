<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\SectionEntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionParentEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;


$this->title(Yii::t('cms', 'Link entries'));

echo CmsSubmenu::make()
    ->model($provider->section);

$this->breadcrumbs([
    Yii::t('cms', 'Entries') => $provider->section->getAdminRoute() + ['#' => 'entries'],
    Yii::t('cms', 'Link entries'),
]);

echo GridContainer::make()
    ->grid(SectionEntryGridView::make()
        ->provider($provider));
