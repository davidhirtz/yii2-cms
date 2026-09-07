<?php

declare(strict_types=1);

/**
 * @see AssetController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider|null $provider
 * @var Entry|Section $parent
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

echo EntryHeader::make()
    ->model($parent);

echo EntrySubmenu::make()
    ->model($parent instanceof Section ? $parent->entry : $parent);

if ($provider) {
    echo GridContainer::make()
        ->grid(FileGridView::make()
            ->provider($provider)
            ->parent($parent));
} else {
    echo GridContainer::make()
        ->grid(AssetGridView::make()
            ->parent($parent));
}
