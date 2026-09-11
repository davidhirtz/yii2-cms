<?php

declare(strict_types=1);

/**
 * @see AssetController::actionCreate()
 *
 * @var View $this
 * @var Entry|Section $model
 * @var FileActiveDataProvider $provider
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetSubmenu;
use Hirtz\Media\Modules\Admin\Data\FileActiveDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo AssetHeader::make()
    ->model($model);

echo AssetSubmenu::make()
    ->model($model);

echo GridContainer::make()
    ->grid(FileGridView::make()
        ->provider($provider)
        ->model($model));
