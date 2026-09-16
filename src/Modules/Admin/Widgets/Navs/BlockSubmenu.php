<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetSubmenuItem;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class BlockSubmenu extends Submenu
{
    /**
     * @use ModelTrait<Block>
     */
    use ModelTrait;

    /**
     * @var array<string, list<string>>
     */
    protected array $additionalActiveRoutes = [];

    /**
     * @param array<string, list<string>> $additionalActiveRoutes keyed by the item the routes belong to
     */
    public function additionalActiveRoutes(array $additionalActiveRoutes): static
    {
        $this->additionalActiveRoutes = $additionalActiveRoutes;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getBlocksItem(),
            $this->getBlockUpdateItem(),
            $this->getEntriesItem(),
            $this->getAssetsItem(),
        );

        parent::configure();
    }

    protected function getBlocksItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('angle-double-left')
            ->label(Yii::t('cms', 'COMMON_BLOCKS'))
            ->url(['/admin/cms/block/index']);
    }

    protected function getBlockUpdateItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('cog')
            ->label($this->model->getAdminType())
            ->routes(['admin/cms/block/update', ...$this->additionalActiveRoutes['block'] ?? []])
            ->url($this->model->getAdminRoute());
    }

    protected function getEntriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->entry_count)
            ->icon('chain')
            ->label(Yii::t('cms', 'COMMON_SECTION_ENTRIES'))
            ->routes(['admin/cms/block-entry', ...$this->additionalActiveRoutes['entries'] ?? []])
            ->url(['/admin/cms/block-entry/index', 'block' => $this->model->id])
            ->visible($this->model->allowsEntries());
    }

    protected function getAssetsItem(): ?NavItem
    {
        return AssetSubmenuItem::make()
            ->badge($this->model->asset_count)
            ->label($this->model->getAttributeLabel('asset_count'))
            ->routes(['admin/cms/block-asset', ...$this->additionalActiveRoutes['assets'] ?? []])
            ->url(BlockAsset::getAdminIndexRoute($this->model))
            ->visible($this->model->allowsAssets());
    }
}
