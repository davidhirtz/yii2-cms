<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Controllers\BlockController;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Yii;

/**
 * @see BlockController::actionDelete()
 *
 * @extends DeleteButton<Block>
 */
class BlockDeleteButton extends DeleteButton
{
    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && $this->webuser->can(Block::AUTH_BLOCK);
    }

    #[Override]
    protected function configure(): void
    {
        $sectionCount = $this->model->getSections()->count();

        if ($sectionCount) {
            $this->message ??= Yii::t('cms', 'BLOCK_DELETE_WARNING', ['count' => $sectionCount]);
        }

        $this->url ??= ['/admin/cms/block/delete', 'id' => $this->model->id];

        parent::configure();
    }
}
