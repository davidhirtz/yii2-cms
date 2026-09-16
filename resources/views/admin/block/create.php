<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\BlockController::actionCreate()
 *
 * @var View $this
 * @var Block $block
 */

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\BlockActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\BlockHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo BlockHeader::make()
    ->title(Yii::t('cms', 'BLOCK_CREATE_TITLE'));

echo FormContainer::make()
    ->form(BlockActiveForm::make()
        ->model($block));
