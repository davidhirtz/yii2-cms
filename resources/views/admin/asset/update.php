<?php

declare(strict_types=1);

/**
 * @see AssetController::actionUpdate()
 *
 * @var View $this
 * @var Asset $asset
 */

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\AssetActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetActionDropdown;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo AssetHeader::make()
    ->model($asset->parent)
    ->content(AssetActionDropdown::make()
        ->model($asset));

echo AssetSubmenu::make()
    ->model($asset->parent);

echo FormContainer::make()
    ->title($this->title)
    ->form(AssetActiveForm::make()
        ->model($asset));
