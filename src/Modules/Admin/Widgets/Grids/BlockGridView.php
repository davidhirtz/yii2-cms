<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Controllers\BlockController;
use Hirtz\Cms\Modules\Admin\Data\BlockActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\EntryRelationCountColumn;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\AssetCountColumn;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<Block>
 * @property BlockActiveDataProvider $provider
 */
class BlockGridView extends GridView
{
    use ModuleTrait;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'block-grid-view';

        /** @see BlockController::actionOrder() */
        $this->orderRoute = ['order'];

        $this->header ??= [
            $this->getSearchInput(),
        ];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getEntryCountColumn(),
            $this->getAssetCountColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ->emptyMessage(Yii::t('cms', 'BLOCK_GRID_SUMMARY_EMPTY'))
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }

    protected function getStatusColumn(): ?Column
    {
        return StatusIconColumn::make()
            ->enableUpdate($this->enableStatusUpdate && $this->webuser->can(Block::AUTH_BLOCK));
    }

    protected function getTypeColumn(): ?Column
    {
        return TypeColumn::make()
            ->url(fn (Block $block) => $block->getAdminRoute())
            ->visible(count(Block::instance()::getTypeDefinitions()) > 1);
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property(Block::instance()->getI18nAttributeName('name'))
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Block $block): string|Stringable
    {
        $name = (string)$block->getI18nAttribute('name');

        return A::make()
            ->class('strong')
            ->content($this->search->markKeywords($name))
            ->href($block->getAdminRoute());
    }

    protected function getEntryCountColumn(): ?Column
    {
        return EntryRelationCountColumn::make();
    }

    protected function getAssetCountColumn(): ?Column
    {
        return AssetCountColumn::make();
    }

    protected function getUpdatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('updated_at');
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    /**
     * @return list<Stringable>
     */
    protected function getButtonColumnContent(Block $block): array
    {
        $buttons = [];

        if ($this->isSortable() && $this->webuser->can(Block::AUTH_BLOCK)) {
            $buttons[] = DraggableSortGridButton::make();
        }

        $buttons[] = ViewGridButton::make()
            ->model($block);

        return $buttons;
    }
}
