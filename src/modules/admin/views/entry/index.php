<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\cms\modules\admin\controllers\EntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\modules\admin\widgets\navs\CmsSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;

$this->title(Yii::t('cms', 'Entries'));

echo CmsSubmenu::make()
    ->model($provider->parent);

echo GridContainer::make()
    ->grid(EntryGridView::make()
        ->provider($provider));
