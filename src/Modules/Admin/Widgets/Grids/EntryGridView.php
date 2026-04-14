<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryCategoryController;
use Hirtz\Cms\Modules\Admin\Controllers\EntryController;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Helpers\FrontendLink;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\AssetCountColumn;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\EntryEntryCountColumn;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\SectionCountColumn;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridSearchForm;
use Hirtz\Skeleton\Widgets\Grids\Traits\StatusGridViewTrait;
use Hirtz\Skeleton\Widgets\Grids\Traits\TypeGridViewTrait;
use Override;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @template T of Entry
 * @extends GridView<T>
 * @property EntryActiveDataProvider $provider
 */
class EntryGridView extends GridView
{
    use ModuleTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;

    protected bool $showUrl = true;
    protected ?bool $showCategories = null;
    protected bool $showCategoryDropdown = true;
    protected int|false $showCategoryDropdownFilterMinCount = 1;
    protected bool $showTypeDropdown = true;
    protected bool $showDeleteButton = false;
    protected ?array $orderRoute = null;

    private ?array $categoryNames = null;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'entry-grid-view';

        $enableCategories = static::getModule()->enableCategories;
        $this->showCategories ??= $enableCategories && count($this->getCategories()) > 0;

        if ($this->showCategoryDropdown) {
            $this->showCategoryDropdown = $enableCategories;
        }

        $this->model ??= Entry::instance();
        $types = $this->model::getTypes();

        if ($enableCategories && $this->provider->type) {
            $this->showCategories = $types[$this->provider->type]['showCategories'] ?? $this->showCategories;
            $this->showCategoryDropdown = $types[$this->provider->type]['showCategoryDropdown'] ?? $this->showCategoryDropdown;
        }

        if ($this->showTypeDropdown) {
            $this->showTypeDropdown = count($types) > 1;
        }

        /**
         * @see EntryController::actionOrder()
         * @see EntryCategoryController::actionOrder()
         */
        $this->orderRoute ??= $this->provider->category
            ? ['entry-category/order', 'category' => $this->provider->category->id]
            : ['order', 'parent' => $this->provider->parent?->id];

        $this->header ??= [
            $this->showTypeDropdown ? $this->getTypeDropdown() : null,
            $this->showCategoryDropdown ? $this->getCategoryDropdown() : null,
            GridSearchForm::make()->grid($this),
        ];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getEntryCountColumn(),
            $this->getSectionCountColumn(),
            $this->getAssetCountColumn(),
            $this->getDateColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getCategoryDropdown(): ?FilterDropdown
    {
        $items = $this->getCategoryDropdownItems();

        return $items
            ? FilterDropdown::make()
                ->items($items)
                ->label(Yii::t('cms', 'All Categories'))
                ->param('category')
                ->filterable($this->showCategoryDropdownFilterMinCount && $this->showCategoryDropdownFilterMinCount < count($items))
            : null;
    }

    protected function getCategoryDropdownItems(): array
    {
        return array_map(fn ($category) => $this->getNestedCategoryNames()[$category->id], $this->getCategories());
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property('name')
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Entry $entry): string
    {
        $name = $entry->getI18nAttribute('name');

        $html = $name
            ? $this->search->markKeywords($name)
            : Yii::t('cms', '[ No title ]');

        $html = A::make()
            ->content($html)
            ->href($this->getRoute($entry))
            ->class($name ? 'strong' : 'strong text-muted');

        if ($this->showUrl) {
            $html .= $this->getUrl($entry);
        }

        if ($this->showCategories) {
            $html .= $this->getCategoryButtons($entry);
        }

        return $html;
    }

    protected function getEntryCountColumn(): ?Column
    {
        return EntryEntryCountColumn::make();
    }

    protected function getSectionCountColumn(): ?Column
    {
        return SectionCountColumn::make();
    }

    protected function getAssetCountColumn(): ?Column
    {
        return AssetCountColumn::make();
    }

    protected function getDateColumn(): ?Column
    {
        return $this->provider->query->orderBy && key($this->provider->query->orderBy) === 'publish_date'
            ? $this->publishDateColumn()
            : $this->updatedAtColumn();
    }

    protected function publishDateColumn(): ?Column
    {
        return DataColumn::make()
            ->property('publish_date')
            ->format('date');
    }

    protected function updatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('updated_at');
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(Entry $entry): array
    {
        $buttons = [];

        if ($this->isSortable() && $this->webuser->can(Entry::AUTH_ENTRY_ORDER)) {
            $buttons[] = $this->getSortableButton();
        }

        if ($this->webuser->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $entry])) {
            $buttons[] = $this->getUpdateButton($entry);
        }

        if ($this->showDeleteButton && $this->webuser->can(Entry::AUTH_ENTRY_DELETE, ['entry' => $entry])) {
            $buttons[] = $this->getDeleteButton($entry);
        }

        return $buttons;
    }

    protected function getSortableButton(): ?Stringable
    {
        return DraggableSortGridButton::make();
    }

    protected function getUpdateButton(Entry $entry): Stringable
    {
        return ViewGridButton::make()
            ->model($entry);
    }

    protected function getDeleteButton(Entry $entry): Stringable
    {
        return DeleteGridButton::make()
            ->model($entry);
    }

    protected function getCategoryButtons(Entry $entry): ?Stringable
    {
        $categoryIds = $entry->getCategoryIds();
        $categories = [];

        foreach ($this->getCategories() as $category) {
            if ($category->hasEntriesEnabled() && in_array($category->id, $categoryIds, true)) {
                $categories[] = Button::make()
                    ->secondary()
                    ->text($category->getI18nAttribute('name'))
                    ->current(['category' => $category->id, 'page' => null])
                    ->addClass('btn-sm');
            }
        }

        return $categories
            ? Div::make()
                ->class('btn-group')
                ->content(...$categories)
            : null;
    }

    protected function getCategories(): array
    {
        return CategoryCollection::getAll();
    }

    protected function getNestedCategoryNames(): array
    {
        return $this->categoryNames ??= Category::indentNestedTree(
            CategoryCollection::getAll(),
            Category::instance()->getI18nAttributeName('name'),
        );
    }

    protected function getUrl(Entry $entry): ?Stringable
    {
        $link = FrontendLink::tag($entry);

        return $link
            ? Div::make()
                ->class('d-none d-md-block small')
                ->content($link)
            : null;
    }

    /**
     * @param T $model
     */
    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return [
            ...Yii::$app->getRequest()->get(),
            ...$model->getAdminRoute(),
            ...$params,
        ];
    }

    #[Override]
    protected function isSortable(): bool
    {
        return $this->provider->category === null && parent::isSortable();
    }
}
