<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockAssetController::actionUpdate()
 *
 * @var View $this
 * @var BlockAsset $asset
 */

use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Forms\AssetActiveForm;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetActionDropdown;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo BlockHeader::make()
    ->model($asset->model)
    ->content(AssetActionDropdown::make()
        ->model($asset));

echo BlockSubmenu::make()
    ->model($asset->model);

echo FormContainer::make()
    ->form(AssetActiveForm::make()
        ->model($asset));
