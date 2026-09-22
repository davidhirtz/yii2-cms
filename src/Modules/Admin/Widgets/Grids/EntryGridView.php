<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryCategoryController;
use Hirtz\Cms\Modules\Admin\Controllers\EntryController;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\EntryEntryCountColumn;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\SectionCountColumn;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Toolbars\CategoryFilterDropdown;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\FrontendLink;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\AssetCountColumn;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\ButtonGroup;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Buttons\DraggableSortButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\TypeFilterDropdown;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Stringable;
use Traversable;
use Yii;

/**
 * @template T of Entry
 * @extends GridView<T>
 * @property EntryActiveDataProvider $provider
 */
class EntryGridView extends GridView
{
    use ModuleTrait;

    public string $tenantParamName = 'tenant';

    protected bool $showUrl = true;
    protected ?bool $showCategories = null;
    protected bool $showCategoryDropdown = true;
    protected bool $showTypeDropdown = true;
    protected bool $showDeleteButton = false;
    /**
     * @var array<int|string, mixed>|null
     */
    protected ?array $orderRoute = null;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'entry-grid-view';

        $enableCategories = static::getModule()->enableCategories;
        $type = $enableCategories ? Entry::instance()::findType($this->provider->type) : null;

        // A grid filtered to one type reads that type. Display is `shows*`, but a type with no categories at all
        // has nothing to show either, whatever it declares.
        if ($type && !$type->allowsCategories()) {
            $this->showCategories = false;
            $this->showCategoryDropdown = false;
        } elseif ($type) {
            $this->showCategories = $type->showsCategories() ?? $this->showCategories ?? true;
            $this->showCategoryDropdown = $type->showsCategoryDropdown() ?? $this->showCategoryDropdown;
        }

        /**
         * @see EntryController::actionOrder()
         * @see EntryCategoryController::actionOrder()
         */
        $this->orderRoute ??= $this->provider->category
            ? ['entry-category/order', 'category' => $this->provider->category->id]
            : ['order', 'parent' => $this->provider->parent?->id];

        $this->header ??= [
            $this->getTenantDropdown(),
            $this->getTypeDropdown(),
            $this->getCategoryDropdown(),
            $this->getSearchInput(),
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

    /**
     * A single-tenant project gets no dropdown at all, and neither does a grid already scoped to one entry's
     * children — they all belong to its tenant, so the filter could only empty the grid.
     */
    protected function getTenantDropdown(): ?Stringable
    {
        if ($this->provider->parent) {
            return null;
        }

        $items = $this->getTenantDropdownItems();

        if (count($items) < 2) {
            return null;
        }

        $manager = Yii::$app->getUrlManager();
        $tenant = $manager instanceof UrlManager ? $manager->getTenantFromRequest(Application::current()->getRequest()) : null;

        return FilterDropdown::make()
            ->default(false)
            ->label($tenant->name ?? Yii::t('cms', 'ENTRY_TENANT_ID_LABEL'))
            ->items($items)
            ->paramName($this->tenantParamName);
    }

    /**
     * The tenant the grid is scoped to comes from the host as often as from the dropdown's parameter, and an empty
     * grid of one tenant says nothing about the others — so the dropdown that reaches them has to stay.
     */
    #[Override]
    protected function isFiltered(): bool
    {
        if (parent::isFiltered()) {
            return true;
        }

        if ($this->provider->tenantId === null) {
            return false;
        }

        foreach (is_array($this->header) ? $this->header : [] as $item) {
            if ($item instanceof FilterDropdown && $item->getParamName() === $this->tenantParamName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function getTenantDropdownItems(): array
    {
        return array_map(fn (Tenant $tenant) => $tenant->name, TenantCollection::getAll());
    }

    protected function getTypeDropdown(): ?Stringable
    {
        return TypeFilterDropdown::make()
            ->model(Entry::instance())
            ->visible($this->showTypeDropdown);
    }

    protected function getCategoryDropdown(): ?Stringable
    {
        return CategoryFilterDropdown::make()
            ->visible($this->showCategoryDropdown);
    }

    protected function getStatusColumn(): ?Column
    {
        return StatusIconColumn::make()
            ->enableUpdate($this->enableStatusUpdate
                && !$this->isPicker()
                && $this->webuser->can(Entry::AUTH_ENTRY));
    }

    protected function getTypeColumn(): ?Column
    {
        return $this->provider->type === null
            ? TypeColumn::make()
                ->url($this->getRecordUrl(...))
                ->visible($this->hasVisibleTypes())
            : null;
    }

    protected function hasVisibleTypes(): bool
    {
        return count(Entry::instance()::getTypeDefinitions()) > 1;
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property('name')
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Entry $entry): string|Stringable
    {
        $name = $entry->getI18nAttribute('name');

        $content = $name
            ? $this->search->markKeywords($name)
            : Yii::t('cms', 'COMMON_NO_TITLE');

        $url = $this->getRecordUrl($entry);
        $class = $name ? 'strong' : 'strong text-muted';

        $html = $url
            ? A::make()->content($content)->href($url)->class($class)
            : Div::make()->content($content)->class($class);

        if ($this->showUrl) {
            $html .= $this->getUrl($entry);
        }

        if ($this->showCategories) {
            $html .= $this->getCategoryButtons($entry);
        }

        return $html;
    }

    /**
     * Whether the grid is a list to pick an entry *from* rather than to navigate. A picker must not lead away
     * from itself — that cancels the flow the user is in — so its rows drill into the subentries instead, its
     * count badges carry no link, and the entry's own page is an external link button.
     */
    protected function isPicker(): bool
    {
        return false;
    }

    /**
     * Where the row's own links lead: the name and the type icon.
     *
     * @return array<array-key, mixed>|string|null
     */
    protected function getRecordUrl(Entry $entry): array|string|null
    {
        if (!$this->isPicker()) {
            return $entry->getAdminRoute() ?: null;
        }

        return $entry->allowsDescendants() && $entry->entry_count
            ? $this->getDescendantUrl($entry)
            : null;
    }

    /**
     * The subentries, which is also what {@see EntryEntryCountColumn} carries.
     *
     * @return array<array-key, mixed>|string
     */
    protected function getDescendantUrl(Entry $entry): array|string
    {
        return Url::current([
            'category' => null,
            'parent' => $entry->id,
            'q' => null,
            'type' => null,
        ]);
    }

    /**
     * The entry's own page, which a picker's name no longer leads to — in a new tab, so the picker survives the
     * detour.
     */
    protected function getAdminLinkButton(Entry $entry): Stringable
    {
        return Button::make()
            ->secondary()
            ->icon('external-link-alt')
            ->tooltip(Yii::t('cms', 'COMMON_OPEN_ADMIN'))
            ->url($entry->getAdminRoute() ?: null)
            ->target('_blank');
    }

    protected function getEntryCountColumn(): ?Column
    {
        return EntryEntryCountColumn::make();
    }

    protected function getSectionCountColumn(): ?Column
    {
        $column = SectionCountColumn::make();
        return $this->isPicker() ? $column->url(null) : $column;
    }

    protected function getAssetCountColumn(): ?Column
    {
        $column = AssetCountColumn::make();
        return $this->isPicker() ? $column->url(null) : $column;
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

    /**
     * @return Traversable<int, Stringable>
     */
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        if ($this->isSortable() && $this->webuser->can(Entry::AUTH_ENTRY)) {
            yield $this->getSortableButton();
        }

        if ($this->webuser->can(Entry::AUTH_ENTRY)) {
            yield $this->getUpdateButton($entry);
        }

        if ($this->showDeleteButton && $this->webuser->can(Entry::AUTH_ENTRY)) {
            yield $this->getDeleteButton($entry);
        }
    }

    protected function getSortableButton(): ?Stringable
    {
        return DraggableSortButton::make();
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

        foreach (CategoryCollection::getAll() as $category) {
            if ($category->allowsEntries() && in_array($category->id, $categoryIds, true)) {
                $categories[] = Button::make()
                    ->secondary()
                    ->text($category->getI18nAttribute('name'))
                    ->current(['category' => $category->id, 'page' => null])
                    ->addClass('btn-sm');
            }
        }

        return $categories ? ButtonGroup::make()->content(...$categories) : null;
    }

    protected function getUrl(Entry $entry): ?Stringable
    {
        return Div::make()
            ->class('d-none d-md-block small')
            ->content(FrontendLink::make()->model($entry));
    }

    #[Override]
    protected function isSortable(): bool
    {
        return $this->provider->category === null && parent::isSortable();
    }
}
