<?php

declare(strict_types=1);

/**
 * @see EntryAssetController::actionEntries()
 *
 * @var View $this
 * @var EntryAsset $asset
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryAssetParentEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo AssetHeader::make()
    ->model($asset);

echo EntrySubmenu::make()
    ->model($asset->model);

echo HintAlert::make()
    ->text(Yii::t('cms', 'ENTRY_ASSET_ENTRIES_HINT'));

echo GridContainer::make()
    ->grid(EntryAssetParentEntryGridView::make()
        ->provider($provider)
        ->asset($asset));
