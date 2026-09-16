<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Data\CategoryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Traits\CategoryGridTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;

/**
 * @template T of Category
 * @extends GridView<T>
 * @property CategoryActiveDataProvider $provider
 */
class EntryCategoryGridView extends GridView
{
    use CategoryGridTrait;
    use ModuleTrait;

    public string $categoryParamName = 'category';
    public bool $showUrl = false;

    #[Override]
    public function configure(): void
    {
        $this->initAncestors();

        $this->attributes['id'] ??= 'entry-category-grid-view';

        $this->rowAttributes ??= fn (Category $category) => [
            'class' => $category->entryCategory ? 'is-selected' : null,
        ];

        $this->header ??= [
            $this->getSearchInput(),
        ];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getBranchCountColumn(),
            $this->getEntryCountColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ->emptyMessage(Yii::t('cms', 'ENTRY_CATEGORY_GRID_SUMMARY_EMPTY'));
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

    protected function isPicker(): bool
    {
        return true;
    }

    /**
     * The picker is the one grid a category is read out of context in — which branch of the tree a name belongs to
     * is only readable from its path, so it is always rendered here. No `#[Override]`: the method comes from a
     * trait this class uses itself.
     */
    protected function showCategoryAncestors(Category $category): bool
    {
        return (bool)$category->parent_id;
    }

    /**
     * @return list<Stringable>
     */
    protected function getButtonColumnContent(Category $category): array
    {
        $buttons = [$this->getAdminLinkButton($category)];

        // Categories can always be removed even, if they were not supposed to have entries enabled
        if ($category->allowsEntries() || $category->entryCategory) {
            $buttons[] = Button::make()
                ->primary()
                ->icon($category->entryCategory ? 'ban' : 'star')
                ->post([
                    $category->entryCategory ? 'delete' : 'create',
                    'entry' => $this->provider->entry->id,
                    'category' => $category->id,
                ]);
        }

        return $buttons;
    }

    /**
     * The category's own page, which the name no longer leads to — in a new tab, so the picker survives the detour.
     */
    protected function getAdminLinkButton(Category $category): Stringable
    {
        return Button::make()
            ->secondary()
            ->icon('external-link-alt')
            ->tooltip(Yii::t('cms', 'COMMON_OPEN_ADMIN'))
            ->url($category->getAdminRoute())
            ->target('_blank');
    }
}
