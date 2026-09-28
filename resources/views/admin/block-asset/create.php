<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockAssetController::actionCreate()
 *
 * @var View $this
 * @var Block $model
 * @var FileActiveDataProvider $provider
 * @var BlockAsset|null $asset
 */

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Media\Modules\Admin\Data\FileActiveDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;

echo BlockHeader::make()
    ->model($model);

echo BlockSubmenu::make()
    ->model($model);

echo HintAlert::make()
    ->text(Yii::t('media', 'ASSET_CREATE_HINT'));

echo GridContainer::make()
    ->grid(FileGridView::make()
        ->provider($provider)
        ->model($model)
        ->asset($asset));
