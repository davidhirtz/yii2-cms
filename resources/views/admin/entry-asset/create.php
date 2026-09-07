<?php

declare(strict_types=1);

/**
 * @see EntryAssetController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider|null $provider
 * @var Entry $entry
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

echo EntryHeader::make()
    ->model($entry);

echo EntrySubmenu::make()
    ->model($entry);

echo GridContainer::make()
    ->grid(FileGridView::make()
        ->provider($provider)
        ->parent($entry));
