<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;

class SectionSubmenu extends Submenu
{
    /**
     * @use ModelTrait<Section>
     */
    use ModelTrait;

    use ModuleTrait;

    protected array $additionalActiveRoutes = [];

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getSectionUpdateItem(),
            $this->getAssetsItem(),
        );

        parent::configure();
    }

    protected function getSectionUpdateItem(): ?NavItem
    {
        return NavItem::make()
            ->icon('cog')
            ->label(Lang::t('skeleton', 'COMMON_GENERAL'))
            ->routes(['admin/cms/section/update', ...$this->additionalActiveRoutes['section'] ?? []])
            ->url($this->model->getAdminRoute());
    }


    protected function getAssetsItem(): ?NavItem
    {
        return NavItem::make()
            ->badge($this->model->asset_count)
            ->icon('photo-film')
            ->label($this->model->getAttributeLabel('asset_count'))
            ->routes(
                [
                    'admin/cms/section-asset/',
                    ...$this->additionalActiveRoutes['assets'] ?? [],
                ]
            )
            ->url(['/admin/cms/section-asset/index', 'section' => $this->model->id]);
    }
}
