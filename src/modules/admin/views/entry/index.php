<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\EntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\CmsSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('cms', 'Entries'));

echo CmsSubmenu::make()
    ->model($provider->parent);

echo GridContainer::make()
    ->grid(EntryGridView::make()
        ->provider($provider));
