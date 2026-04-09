<?php

declare(strict_types=1);

/**
 * @see AssetController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var Entry|Section $parent
 */

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\CmsSubmenu;
use Hirtz\Media\Modules\Admin\Widgets\Grids\FileGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('media', 'Assets'));

echo CmsSubmenu::make()
    ->model($parent);

$this->addBreadcrumb(Yii::t('media', 'Assets'), [$parent instanceof Section
    ? '/admin/cms/section/update'
    : '/admin/cms/entry/update', 'id' => $parent->id, '#' => 'assets',
]);

echo GridContainer::make()
    ->grid(FileGridView::make()
        ->provider($provider)
        ->parent($parent));
