<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Media\Modules\Admin\Widgets\Buttons\FileButtonsTrait;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;

class AssetParentActionDropdown extends ActionDropdown
{
    use FileButtonsTrait;

    /**
     * @use ProviderTrait<AssetArrayDataProvider>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getFileUploadButton(),
            $this->getFileImportButton(),
            $this->getAssetLinkButton(),
        );

        parent::configure();
    }

    protected function getAssetLinkButton(): ?Stringable
    {
        return Button::make()
            ->primary()
            ->text(Lang::t('cms', 'COMMON_LINK_ASSETS'))
            ->icon('images')
            ->url($this->getFileUploadRoute());
    }

    protected function getFileUploadRoute(): array
    {
        return $this->provider->parent instanceof Section
            ? ['/admin/cms/section-asset/create', 'section' => $this->provider->parent->id]
            : ['/admin/cms/entry-asset/create', 'entry' => $this->provider->parent->id];
    }
}
