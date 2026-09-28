<?php

declare(strict_types=1);

/**
 * @see EntryAssetController::actionCreate()
 *
 * @var View $this
 * @var Entry $model
 * @var FileActiveDataProvider $provider
 * @var EntryAsset|null $asset
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Media\Modules\Admin\Data\FileActiveDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;

echo EntryHeader::make()
    ->model($model);

echo EntrySubmenu::make()
    ->model($model);

echo HintAlert::make()
    ->text(Yii::t('media', 'ASSET_CREATE_HINT'));

echo GridContainer::make()
    ->grid(FileGridView::make()
        ->provider($provider)
        ->model($model)
        ->asset($asset));
