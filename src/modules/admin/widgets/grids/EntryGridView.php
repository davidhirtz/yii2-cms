<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\collections\CategoryCollection;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryCategoryController;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\helpers\FrontendLink;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\AssetCountColumn;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\EntryCountColumn;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\SectionCountColumn;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\ButtonToolbar;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\CreateButton;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\DeleteButton;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\DraggableSortButton;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\ViewButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\widgets\grids\FilterDropdown;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
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
 * @property EntryActiveDataProvider $dataProvider
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
     * @var bool|null whether category column should be visible when {@see EntryActiveDataProvider::$type} is null
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

    /**
     * @var string|null the date format used in the date column, defaults to null which means the date format is
     */
    public ?string $dateFormat = null;

    private ?array $categoryNames = null;

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'entry-grid');

        $enableCategories = static::getModule()->enableCategories;
        $this->showCategories ??= $enableCategories && count($this->getCategories()) > 0;

        if ($this->showCategoryDropdown) {
            $this->showCategoryDropdown = $enableCategories;
        }

        $types = $this->getModel()::getTypes();

        if ($enableCategories && $this->dataProvider->type) {
            $this->showCategories = $types[$this->dataProvider->type]['showCategories'] ?? $this->showCategories;
            $this->showCategoryDropdown = $types[$this->dataProvider->type]['showCategoryDropdown'] ?? $this->showCategoryDropdown;
        }

        if ($this->showTypeDropdown) {
            $this->showTypeDropdown = count($types) > 1;
        }

        $this->columns ??= [
            $this->statusColumn(),
            $this->typeColumn(),
            $this->nameColumn(),
            $this->entryCountColumn(),
            $this->sectionCountColumn(),
            $this->assetCountColumn(),
            $this->dateColumn(),
            $this->buttonsColumn(),
        ];

        /**
         * @see EntryController::actionOrder()
         * @see EntryCategoryController::actionOrder()
         */
        $this->orderRoute ??= $this->dataProvider->category
            ? ['entry-category/order', 'category' => $this->dataProvider->category->id]
            : ['order', 'parent' => $this->dataProvider->parent?->id];

        parent::init();
    }

    protected function initHeader(): void
    {
        $this->header ??= [
            [
                $this->showTypeDropdown ? $this->getTypeDropdown() : null,
                $this->showCategoryDropdown ? $this->getCategoryDropdown() : null,
                $this->search->getToolbarItem(),
            ],
        ];
    }

    protected function getCategoryDropdown(): ?FilterDropdown
    {
        $items = $this->getCategoryDropdownItems();

        return $items
            ? new FilterDropdown(
                $items,
                Yii::t('cms', 'Category'),
                'category',
                filter: $this->showCategoryDropdownFilterMinCount && $this->showCategoryDropdownFilterMinCount < count($items),
            ) : null;
    }

    protected function getCategoryDropdownItems(): array
    {
        return array_map(fn ($category) => $this->getNestedCategoryNames()[$category->id], $this->getCategories());
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                $this->getCreateEntryButton(),
            ],
        ];
    }

    protected function getCreateEntryButton(): ?Stringable
    {
        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_CREATE)) {
            return null;
        }

        return Yii::createObject(CreateButton::class, [
            Yii::t('cms', 'New Entry'),
            [
                '/admin/entry/create',
                ...Yii::$app->getRequest()->getQueryParams(),
                'type' => $this->dataProvider->type,
            ]
        ]);
    }

    protected function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Entry $entry) {
                $name = $entry->getI18nAttribute('name');

                $html = $name
                    ? Html::markKeywords(Html::encode($name), $this->search->getKeywords())
                    : Yii::t('cms', '[ No title ]');

                $html = A::make()
                    ->html($html)
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
        ];
    }

    protected function entryCountColumn(): array
    {
        return [
            'class' => EntryCountColumn::class,
            'attribute' => 'entry_count',
        ];
    }

    protected function sectionCountColumn(): array
    {
        return [
            'class' => SectionCountColumn::class,
            'attribute' => 'section_count',
        ];
    }

    protected function assetCountColumn(): array
    {
        return [
            'class' => AssetCountColumn::class,
            'attribute' => 'asset_count',
        ];
    }

    protected function dateColumn(): array
    {
        return $this->dataProvider->query->orderBy && key($this->dataProvider->query->orderBy) === 'publish_date'
            ? $this->publishDateColumn()
            : $this->updatedAtColumn();
    }

    protected function publishDateColumn(): array
    {
        return [
            'attribute' => 'publish_date',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => fn (Entry $entry) => $this->dateFormat
                ? $entry->publish_date->format($this->dateFormat)
                : Yii::$app->getFormatter()->asDate($entry->publish_date)
        ];
    }

    protected function updatedAtColumn(): array
    {
        return [
            'attribute' => 'updated_at',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => fn (Entry $entry) => $this->dateFormat
                ? $entry->updated_at->format($this->dateFormat)
                : Timeago::tag($entry->updated_at)
        ];
    }

    protected function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => fn (Entry $entry): array => $this->getRowButtons($entry)
        ];
    }

    protected function getRowButtons(Entry $entry): array
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
        return Yii::createObject(DraggableSortButton::class);
    }

    protected function getUpdateButton(Entry $entry): Stringable
    {
        return Yii::createObject(ViewButton::class, [$entry]);
    }

    protected function getDeleteButton(Entry $entry): Stringable
    {
        return Yii::createObject(DeleteButton::class, [$entry]);
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

        return $categories ? ButtonToolbar::make()->html(...$categories) : null;
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
        return $link ? Html::div($link, ['class' => 'd-none d-md-block small']) : null;
    }

    /**
     * @param T $model
     * @noinspection PhpDocSignatureInspection
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

    /**
     * @return T
     */
    #[Override]
    public function getModel(): Entry
    {
        return Entry::instance();
    }
}
