<?php

declare(strict_types=1);

/**
 * @see SectionAssetController::actionCreate()
 *
 * @var View $this
 * @var Section $model
 * @var FileActiveDataProvider $provider
 */

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Media\Modules\Admin\Data\FileActiveDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo SectionHeader::make()
    ->model($model);

echo SectionSubmenu::make()
    ->model($model);

echo GridContainer::make()
    ->grid(FileGridView::make()
        ->provider($provider)
        ->model($model));
