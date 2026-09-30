<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Modules\Admin\Controllers\EntryAssetController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Override;
use Stringable;
use Traversable;
use Yii;

/**
 * @extends EntryGridView<Entry>
 */
class EntryAssetParentEntryGridView extends EntryGridView
{
    public bool $showSelection = false;

    protected EntryAsset $asset;

    public function asset(EntryAsset $asset): static
    {
        $this->asset = $asset;
        return $this;
    }

    #[Override]
    protected function isPicker(): bool
    {
        return true;
    }

    /**
     * @see EntryAssetController::actionDuplicate()
     * @return Traversable<int, Stringable>
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        yield $this->getAdminLinkButton($entry);

        if ($entry->id === $this->asset->model_id || !$entry->allowsAssets()) {
            return;
        }

        yield Button::make()
            ->primary()
            ->icon('paste')
            ->tooltip(Yii::t('cms', 'ENTRY_ASSET_BUTTON_COPY_TO_ENTRY'))
            ->post(['duplicate', 'id' => $this->asset->id, 'entry' => $entry->id], true);
    }
}
