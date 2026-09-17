<?php

declare(strict_types=1);

/**
 * @see EntryAssetController::actionUpdate()
 *
 * @var View $this
 * @var EntryAsset $asset
 */

use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\EntrySubmenu;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\FrontendLink;
use Hirtz\Media\Modules\Admin\Widgets\Forms\AssetActiveForm;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetActionDropdown;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo AssetHeader::make()
    ->model($asset)
    ->subheading(FrontendLink::findInChain($asset)?->addClass('hidden-sticky'))
    ->content(AssetActionDropdown::make()
        ->model($asset));

echo EntrySubmenu::make()
    ->model($asset->model);

echo FormContainer::make()
    ->form(AssetActiveForm::make()
        ->model($asset));
