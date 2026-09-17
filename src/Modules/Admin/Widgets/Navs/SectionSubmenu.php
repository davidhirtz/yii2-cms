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
            $this->getSectionUpdateItem(),
            $this->getEntriesItem(),
            $this->getAssetsItem(),
        );

        parent::configure();
    }

    protected function getSectionUpdateItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('cog')
            ->label($this->model->getAdminType())
            ->routes(['admin/cms/section/update', ...$this->additionalActiveRoutes['section'] ?? []])
            ->url($this->model->getAdminRoute() ?: null);
    }

    protected function getEntriesItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->entry_count)
            ->icon('chain')
            ->label(Yii::t('cms', 'COMMON_SECTION_ENTRIES'))
            ->routes(
                [
                    'admin/cms/section-entry',
                    ...$this->additionalActiveRoutes['entries'] ?? [],
                ]
            )
            ->url(['/admin/cms/section-entry/index', 'section' => $this->model->id])
            ->visible($this->model->allowsEntries());
    }

    protected function getAssetsItem(): ?NavItem
    {
        return AssetSubmenuItem::make()
            ->badge($this->model->asset_count)
            ->label($this->model->getAttributeLabel('asset_count'))
            ->routes(
                [
                    'admin/cms/section-asset',
                    ...$this->additionalActiveRoutes['assets'] ?? [],
                ]
            )
            ->url(SectionAsset::getAdminIndexRoute($this->model))
            ->visible($this->model->allowsAssets());
    }
}
