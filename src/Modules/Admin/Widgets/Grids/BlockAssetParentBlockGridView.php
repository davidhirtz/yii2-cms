<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Modules\Admin\Controllers\BlockAssetController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Override;
use Stringable;
use Yii;

class BlockAssetParentBlockGridView extends BlockGridView
{
    public bool $enableStatusUpdate = false;

    protected BlockAsset $asset;

    public function asset(BlockAsset $asset): static
    {
        $this->asset = $asset;
        return $this;
    }

    /**
     * @see BlockAssetController::actionDuplicate()
     * @return list<Stringable>
     */
    #[Override]
    protected function getButtonColumnContent(Block $block): array
    {
        $buttons = [
            ViewGridButton::make()
                ->model($block),
        ];

        if ($block->id !== $this->asset->model_id && $block->allowsAssets()) {
            $buttons[] = Button::make()
                ->primary()
                ->icon('paste')
                ->tooltip(Yii::t('cms', 'BLOCK_ASSET_BUTTON_COPY_TO_BLOCK'))
                ->post(['duplicate', 'id' => $this->asset->id, 'block' => $block->id], true);
        }

        return $buttons;
    }
}
