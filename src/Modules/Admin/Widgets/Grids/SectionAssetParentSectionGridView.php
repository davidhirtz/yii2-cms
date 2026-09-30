<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Modules\Admin\Controllers\SectionAssetController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Override;
use Stringable;
use Yii;

/**
 * The sections of the asset's entry, to pick the one a copy goes to: no selection, no status toggle, no ordering.
 */
class SectionAssetParentSectionGridView extends SectionGridView
{
    public bool $enableStatusUpdate = false;
    public bool $showSelection = false;

    protected SectionAsset $asset;

    public function asset(SectionAsset $asset): static
    {
        $this->asset = $asset;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->orderRoute = null;
    }

    /**
     * @see SectionAssetController::actionDuplicate()
     * @return list<Stringable>
     */
    #[Override]
    protected function getButtonColumnContent(Section $section): array
    {
        $buttons = [
            ViewGridButton::make()
                ->model($section),
        ];

        if ($section->id !== $this->asset->model_id && $section->allowsAssets()) {
            $buttons[] = Button::make()
                ->primary()
                ->icon('paste')
                ->tooltip(Yii::t('cms', 'SECTION_ASSET_BUTTON_COPY_TO_SECTION'))
                ->post(['duplicate', 'id' => $this->asset->id, 'section' => $section->id], true);
        }

        return $buttons;
    }
}
