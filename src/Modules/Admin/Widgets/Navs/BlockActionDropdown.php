<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\BlockDeleteButton;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;
use Yii;

class BlockActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<Block>
     */
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getCreateBlockButton(),
            $this->getDeleteButton(),
        );

        parent::configure();
    }

    protected function getCreateBlockButton(): ?Stringable
    {
        return Button::make()
            ->primary()
            ->text(Yii::t('cms', 'BLOCK_NEW_BLOCK'))
            ->icon('plus')
            ->roles([Block::AUTH_BLOCK])
            ->url(['/admin/cms/block/create']);
    }

    protected function getDeleteButton(): ?Stringable
    {
        return BlockDeleteButton::make()
            ->model($this->model);
    }
}
