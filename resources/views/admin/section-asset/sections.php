<?php

declare(strict_types=1);

/**
 * @see SectionAssetController::actionSections()
 *
 * @var View $this
 * @var SectionAsset $asset
 * @var SectionActiveDataProvider $provider
 */

use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Modules\Admin\Controllers\SectionAssetController;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionAssetParentSectionGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionSubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo AssetHeader::make()
    ->model($asset);

echo SectionSubmenu::make()
    ->model($asset->model);

echo HintAlert::make()
    ->text(Yii::t('cms', 'SECTION_ASSET_SECTIONS_HINT'));

echo GridContainer::make()
    ->grid(SectionAssetParentSectionGridView::make()
        ->provider($provider)
        ->asset($asset));
