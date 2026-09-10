<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\AssetThumbnailColumn;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Models\File;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Traits\AssetGridViewTrait;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;

/**
 * @template T of Asset
 * @extends GridView<T>
 *
 * @property AssetArrayDataProvider $provider
 */
class AssetGridView extends GridView
{
    use AssetGridViewTrait;
    use ModuleTrait;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'asset-grid-view';

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getThumbnailColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getDimensionsColumn(),
            $this->getButtonColumn(),
        ];

        $parent = $this->provider->parent;
        $this->orderRoute = ['/admin/cms/asset/order', $parent->getParamName() => $parent->id];

        parent::configure();
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ->message(Lang::t('cms', 'ASSET_GRID_SUMMARY_EMPTY'))
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }

    protected function getStatusColumn(): ?Column
    {
        return StatusIconColumn::make();
    }

    protected function getTypeColumn(): ?Column
    {
        return TypeColumn::make()
            ->url(fn (Asset $model) => $model->getAdminRoute());
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(Asset $asset): array
    {
        $buttons = [];

        if ($this->isSortable() && $this->provider->getCount() > 1) {
            if ($asset->isEntryAsset()
                ? $this->webuser->can(Entry::AUTH_ENTRY_ASSET_ORDER, ['entry' => $asset->entry])
                : $this->webuser->can(Section::AUTH_SECTION_ASSET_ORDER, ['section' => $asset->section])
            ) {
                $buttons[] = DraggableSortGridButton::make();
            }
        }

        if ($this->webuser->can(File::AUTH_FILE_UPDATE, ['file' => $asset->file])) {
            $buttons[] = $this->getFileUpdateButton($asset);
        }

        $permission = $asset->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_UPDATE
            : Section::AUTH_SECTION_ASSET_UPDATE;

        if ($this->webuser->can($permission, ['asset' => $asset])) {
            $buttons[] = ViewGridButton::make()
                ->model($asset);
        }

        $permission = $asset->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_DELETE
            : Section::AUTH_SECTION_ASSET_DELETE;

        if ($this->webuser->can($permission, ['asset' => $asset])) {
            $buttons[] = $this->getDeleteButton($asset);
        }

        return $buttons;
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property(Asset::instance()->getI18nAttributeName('name'))
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Asset $asset): ?Stringable
    {
        $name = $asset->getI18nAttribute('name');

        $content = $name
            ? Div::make()
                ->class('strong')
                ->text($name)
            : Div::make()
                ->class('text-muted')
                ->text($asset->file->name);

        return $this->canUpdateAsset($asset)
            ? A::make()
                ->content($content)
                ->href($asset->getAdminRoute())
            : $content;
    }

    protected function getThumbnailColumn(): ?Column
    {
        return AssetThumbnailColumn::make()
            ->url(fn (Asset $asset): ?array => $this->canUpdateAsset($asset) ? $asset->getAdminRoute() : null);
    }

    protected function canUpdateAsset(Asset $asset): bool
    {
        $permissionName = $asset->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_UPDATE : Section::AUTH_SECTION_ASSET_UPDATE;
        return Yii::$app->getUser()->can($permissionName, ['asset' => $asset]);
    }
}
