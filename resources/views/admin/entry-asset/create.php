<?php

declare(strict_types=1);

/**
 * @see EntryAssetController::actionCreate()
 *
 * @var View $this
 * @var Entry $model
 * @var FileActiveDataProvider $provider
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Media\Modules\Admin\Data\FileActiveDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo EntryHeader::make()
    ->model($model);

echo EntrySubmenu::make()
    ->model($model);

echo GridContainer::make()
    ->grid(FileGridView::make()
        ->provider($provider)
        ->model($model));
