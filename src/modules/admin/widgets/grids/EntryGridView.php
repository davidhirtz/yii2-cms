<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\collections\CategoryCollection;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryCategoryController;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveprovider;
use davidhirtz\yii2\cms\modules\admin\helpers\FrontendLink;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\AssetCountColumn;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\EntryCountColumn;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\SectionCountColumn;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\DeleteGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\DraggableSortGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\ViewGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\CreateButton;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\FilterDropdown;
use davidhirtz\yii2\skeleton\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\timeago\Timeago;
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

    /**
     * @var bool whether entry urls should be displayed in the name column
     */
    public bool $showUrl = true;

    /**
     * @var bool|null whether category column should be visible when {@see EntryActiveprovider::$type} is null
     */
    public ?bool $showCategories = null;

    /**
     * @var bool whether categories should be selectable via dropdown
     */
    public bool $showCategoryDropdown = true;

    /**
     * @var int|false defines when dropdown filter text field is shown for category dropdown
     */
    public int|false $showCategoryDropdownFilterMinCount = 1;

    /**
     * @var bool whether entry types should be selectable via dropdown
     */
    public bool $showTypeDropdown = true;

    /**
     * @var bool whether the delete-button should be visible in the entry grid
     */
    public bool $showDeleteButton = false;

    /**
     * @var array|null set to null for inheritors to override
     */
    public ?array $orderRoute = null;

    private ?array $categoryNames = null;

    #[Override]
    public function configure(): void
    {
        $this->attributes['id'] ??= 'entries';

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
            $this->search->getToolbarItem(),
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

        $this->footer ??= [
            $this->getCreateEntryButton(),
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

    protected function getCreateEntryButton(): ?Stringable
    {
        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_CREATE)) {
            return null;
        }

        return CreateButton::make()
            ->text(Yii::t('cms', 'New Entry'))
            ->href([
                '/admin/entry/create',
                ...Yii::$app->getRequest()->getQueryParams(),
                'type' => $this->provider->type,
            ]);
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
            ? Html::markKeywords(Html::encode($name), $this->search->getKeywords())
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
        return EntryCountColumn::make();
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
        return TimeagoColumn::make()
            ->property('updated_at');
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(Entry $entry): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($this->isSortable() && $user->can(Entry::AUTH_ENTRY_ORDER)) {
            $buttons[] = $this->getSortableButton();
        }

        if ($user->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $entry])) {
            $buttons[] = $this->getUpdateButton($entry);
        }

        if ($this->showDeleteButton && $user->can(Entry::AUTH_ENTRY_DELETE, ['entry' => $entry])) {
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

    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return [
            ...Yii::$app->getRequest()->get(),
            ...$model->getAdminRoute(),
            ...$params,
        ];
    }
}
