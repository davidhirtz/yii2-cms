<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\modules\admin\controllers\EntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\modules\admin\data\EntryActiveDataProvider;
use Hirtz\Cms\modules\admin\widgets\grids\EntryGridView;
use Hirtz\Cms\modules\admin\widgets\navs\CmsSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;

$this->title(Yii::t('cms', 'Entries'));

echo CmsSubmenu::make()
    ->model($provider->parent);

echo GridContainer::make()
    ->grid(EntryGridView::make()
        ->provider($provider));
