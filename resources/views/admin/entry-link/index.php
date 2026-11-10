<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryLinkController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 * @var list<int> $types
 * @var bool $anyTenant
 * @var string|null $language
 */

use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryLinkGridView;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('cms', 'ENTRY_LINK_PICKER_TITLE'));

// The modal selects `#picker` out of the page.
echo Div::make()
    ->attribute('id', 'picker')
    ->content(GridContainer::make()
        ->grid(EntryLinkGridView::make()
            ->provider($provider)
            ->types($types)
            ->showTenantDropdown($anyTenant)
            ->language($language)));
