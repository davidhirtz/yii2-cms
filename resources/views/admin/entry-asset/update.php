<?php

declare(strict_types=1);

/**
 * @see EntryAssetController::actionUpdate()
 *
 * @var View $this
 * @var Asset $asset
 */

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\AssetActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\AssetActionDropdown;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntryHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo EntryHeader::make()
    ->content(AssetActionDropdown::make()->model($asset))
    ->model($asset->entry);

echo EntrySubmenu::make()
    ->model($asset->entry);

echo FormContainer::make()
    ->title($this->title)
    ->form(AssetActiveForm::make()
        ->model($asset));
