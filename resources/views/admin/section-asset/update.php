<?php

declare(strict_types=1);

/**
 * @see SectionAssetController::actionUpdate()
 *
 * @var View $this
 * @var SectionAsset $asset
 */

use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Modules\Admin\Controllers\SectionAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Forms\AssetActiveForm;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetActionDropdown;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo SectionHeader::make()
    ->model($asset->model)
    ->content(AssetActionDropdown::make()
        ->model($asset));

echo SectionSubmenu::make()
    ->model($asset->model);

echo FormContainer::make()
    ->form(AssetActiveForm::make()
        ->model($asset));
