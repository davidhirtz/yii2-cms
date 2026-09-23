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

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            block: $this->getBlockUpdateItem(),
            assets: $this->getAssetsItem(),
            entries: $this->getEntriesItem(),
            sections: $this->getSectionsItem(),
        );

        parent::configure();
    }

    protected function getBlockUpdateItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('cog')
            ->label($this->model->getAdminType())
            ->addRoute('admin/cms/block/update')
            ->url($this->model->getAdminRoute());
    }

    /**
     * Only once the block is placed somewhere: an unused block has nothing to list, and the tab would say so at
     * the cost of a page of its own.
     */
    protected function getSectionsItem(): ?NavItem
    {
        if (!$this->model->section_count) {
            return null;
        }

        return NavItem::make()
            ->badge($this->model->section_count)
            ->icon('th-list')
            ->label(Yii::t('cms', 'COMMON_SECTIONS'))
            ->addRoute('admin/cms/block-section')
            ->url(['/admin/cms/block-section/index', 'block' => $this->model->id]);
    }

    protected function getEntriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->entry_count)
            ->icon('chain')
            ->label(Yii::t('cms', 'ENTRY_RELATION_NAV_ENTRIES'))
            ->addRoute('admin/cms/block-entry')
            ->url(['/admin/cms/block-entry/index', 'block' => $this->model->id])
            ->visible($this->model->allowsEntries());
    }

    protected function getAssetsItem(): ?NavItem
    {
        return AssetSubmenuItem::make()
            ->badge($this->model->asset_count)
            ->label($this->model->getAttributeLabel('asset_count'))
            ->addRoute('admin/cms/block-asset')
            ->url(BlockAsset::getAdminIndexRoute($this->model))
            ->visible($this->model->allowsAssets());
    }
}
