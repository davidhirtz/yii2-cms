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
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\TypeFilterDropdown;
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

    // A block belongs to nothing, so there is no order to drag it into; the grid sorts by its columns instead.
    protected ?array $orderRoute = null;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'block-grid-view';

        $this->header ??= [
            $this->getTypeDropdown(),
            $this->getSearchInput(),
        ];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getSectionCountColumn(),
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

    protected function getTypeDropdown(): ?Stringable
    {
        return TypeFilterDropdown::make()
            ->model(Block::instance());
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

    /**
     * @see BlockController::actionSections()
     */
    protected function getSectionCountColumn(): ?Column
    {
        return BadgeColumn::make()
            ->property('section_count')
            ->url(fn (Block $block) => ['sections', 'id' => $block->id]);
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
        return [
            ViewGridButton::make()
                ->model($block),
        ];
    }
}
