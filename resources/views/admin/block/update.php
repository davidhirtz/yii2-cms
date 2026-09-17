<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockController::actionUpdate()
 *
 * @var View $this
 * @var Block $block
 */

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\BlockActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockActionDropdown;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo BlockHeader::make()
    ->model($block)
    ->content(BlockActionDropdown::make()
        ->model($block));

echo BlockSubmenu::make()
    ->model($block);

echo FormContainer::make()
    ->form(BlockActiveForm::make()
        ->model($block));
