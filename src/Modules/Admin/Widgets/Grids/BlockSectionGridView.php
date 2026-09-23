<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\BlockSectionController;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\SelectionTrait;
use Override;
use Stringable;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * Every section that places one block, which is what makes a block safe to change or delete. It leads out of
 * itself on purpose — the section is where the placement is edited — so it is not a picker.
 *
 * Its delete routes are relative, so the grid only renders where
 * {@see \Hirtz\Cms\Modules\Admin\Controllers\BlockSectionController} serves it.
 *
 * @extends GridView<Section>
 */
class BlockSectionGridView extends GridView
{
    use SelectionTrait;

    /**
     * @var bool whether each row offers a delete button of its own beside the selection, which deletes the
     * section rather than only its placement.
     */
    public bool $showDeleteButton = false;

    protected string $layout = '{summary}{items}{pager}{footer}';
    protected ?array $orderRoute = null;

    public Block $block;

    public function block(Block $block): static
    {
        $this->block = $block;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'block-section-grid-view';

        $this->provider ??= new ActiveDataProvider([
            'query' => Section::find()
                ->andWhere(['block_id' => $this->block->id])
                ->with('entry')
                ->orderBy(['updated_at' => SORT_DESC]),
        ]);

        foreach ($this->provider->getModels() as $section) {
            $section->populateBlockRelation($this->block);
        }

        $this->configureSelection();

        $this->columns ??= [
            $this->getCheckboxColumn(),
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getEntryColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ->emptyMessage(Yii::t('cms', 'BLOCK_SECTION_GRID_SUMMARY_EMPTY'))
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }

    protected function canDeleteSelection(): bool
    {
        return $this->webuser->can(Entry::AUTH_ENTRY);
    }

    protected function getDeleteSelectionLabel(): string
    {
        return Yii::t('cms', 'SECTION_BUTTON_DELETE_SELECTED');
    }

    protected function getDeleteSelectionMessage(): string
    {
        return Yii::t('cms', 'SECTION_CONFIRM_DELETE_SELECTED');
    }

    /**
     * @see BlockSectionController::actionDeleteAll()
     */
    protected function getDeleteSelectionRoute(): array
    {
        return ['delete-all', 'block' => $this->block->id];
    }

    protected function getStatusColumn(): ?Column
    {
        return StatusIconColumn::make();
    }

    protected function getTypeColumn(): ?Column
    {
        return TypeColumn::make()
            ->url(fn (Section $section) => $this->getSectionUrl($section));
    }

    protected function getEntryColumn(): ?Column
    {
        return LinkColumn::make()
            ->property('entry_id')
            ->title(Yii::t('cms', 'SECTION_ENTRY_ID_LABEL'))
            ->value(fn (Section $section) => $section->entry->getI18nAttribute('name'))
            ->url(fn (Section $section) => $this->getSectionUrl($section));
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
    protected function getButtonColumnContent(Section $section): array
    {
        if (!$this->webuser->can(Entry::AUTH_ENTRY)) {
            return [];
        }

        $buttons = [
            ViewGridButton::make()
                ->url($this->getSectionUrl($section) ?: null),
        ];

        if ($this->showDeleteButton) {
            $buttons[] = DeleteGridButton::make()
                ->label(Yii::t('cms', 'SECTION_BUTTON_DELETE'))
                ->title(Yii::t('cms', 'SECTION_CONFIRM_DELETE'))
                ->model($section)
                ->url(['delete', 'id' => $section->id]);
        }

        return $buttons;
    }

    /**
     * @return array<int|string, mixed>|false
     */
    protected function getSectionUrl(Section $section): array|false
    {
        return $this->webuser->can(Entry::AUTH_ENTRY) ? $section->getAdminRoute() : false;
    }
}
