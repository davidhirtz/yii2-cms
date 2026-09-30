<?php

declare(strict_types=1);

/**
 * @see BlockAssetController::actionBlocks()
 *
 * @var View $this
 * @var BlockAsset $asset
 * @var BlockActiveDataProvider $provider
 */

use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Modules\Admin\Controllers\BlockAssetController;
use Hirtz\Cms\Modules\Admin\Data\BlockActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\BlockAssetParentBlockGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo AssetHeader::make()
    ->model($asset);

echo BlockSubmenu::make()
    ->model($asset->model);

echo HintAlert::make()
    ->text(Yii::t('cms', 'BLOCK_ASSET_BLOCKS_HINT'));

echo GridContainer::make()
    ->grid(BlockAssetParentBlockGridView::make()
        ->provider($provider)
        ->asset($asset));
