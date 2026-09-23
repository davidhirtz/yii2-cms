<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Modules\Admin\Widgets\Navs\AssetSubmenuItem;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class SectionSubmenu extends Submenu
{
    /**
     * @use ModelTrait<Section>
     */
    use ModelTrait;

    use ModuleTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            section: $this->getSectionUpdateItem(),
            assets: $this->getAssetsItem(),
            entries: $this->getEntriesItem(),
        );

        $this->backUrl ??= $this->getAdminBackUrl($this->model);

        parent::configure();
    }

    protected function getSectionUpdateItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('cog')
            ->label($this->model->getAdminType())
            ->addRoute('admin/cms/section/update')
            ->url($this->model->getAdminRoute() ?: null);
    }

    protected function getEntriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->entry_count)
            ->icon('chain')
            ->label(Yii::t('cms', 'ENTRY_RELATION_NAV_ENTRIES'))
            ->addRoute('admin/cms/section-entry')
            ->url(['/admin/cms/section-entry/index', 'section' => $this->model->id])
            ->visible($this->model->allowsEntries());
    }

    protected function getAssetsItem(): ?NavItem
    {
        return AssetSubmenuItem::make()
            ->badge($this->model->asset_count)
            ->label($this->model->getAttributeLabel('asset_count'))
            ->addRoute('admin/cms/section-asset')
            ->url(SectionAsset::getAdminIndexRoute($this->model))
            ->visible($this->model->allowsAssets());
    }
}
