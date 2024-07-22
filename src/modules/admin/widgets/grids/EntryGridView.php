<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryCategoryController;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\AssetCountColumn;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\EntryCountColumn;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\SectionCountColumn;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\timeago\Timeago;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\helpers\Url;

/**
 * @extends GridView<Entry>
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
    public int|false $showCategoryDropdownFilterMinCount = 50;

    /**
     * @var bool whether entry types should be selectable via dropdown
     */
    public bool $showTypeDropdown = true;

    /**
     * @var bool whether the delete-button should be visible in the entry grid
     */
    public bool $showDeleteButton = false;

    /**
     * @see EntryController::actionUpdateAll()
     */
    public array $selectionRoute = ['/admin/entry/update-all'];

    /**
     * @var array|null set to null for inheritors to override
     */
    public ?array $orderRoute = null;

    /**
     * @var string|null the date format used in the date column, defaults to null which means the date format is
     */
    public ?string $dateFormat = null;

    private ?array $_categories = null;
    private ?array $_categoryNames = null;

    public function init(): void
    {
        $this->id = $this->getId(false) ?? 'entries';

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

        $this->type = $this->dataProvider->type;

        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->typeColumn(),
                $this->nameColumn(),
                $this->entryCountColumn(),
                $this->sectionCountColumn(),
                $this->assetCountColumn(),
                $this->dateColumn(),
                $this->buttonsColumn(),
            ];
        }

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
                [
                    'content' => $this->typeDropdown(),
                    'options' => ['class' => 'col-12 col-md-3'],
                    'visible' => $this->showTypeDropdown,
                ],
                [
                    'content' => $this->categoryDropdown(),
                    'options' => ['class' => 'col-12 col-md-3'],
                    'visible' => $this->showCategoryDropdown,
                ],
                [
                    'content' => $this->getSearchInput(),
                    'options' => ['class' => 'col-12 col-md-6'],
                ],
                'options' => [
                    'class' => $this->showCategoryDropdown || $this->showTypeDropdown ? 'justify-content-between' : 'justify-content-end',
                ],
            ],
        ];
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                [
                    'content' => $this->getCreateEntryButton() . ($this->showSelection ? $this->getSelectionButton() : ''),
                    'options' => ['class' => 'col'],
                ],
            ],
        ];
    }

    protected function getCreateEntryButton(): string
    {
        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_CREATE)) {
            return '';
        }

        $route = array_merge(['/admin/entry/create'], Yii::$app->getRequest()->getQueryParams(), ['type' => $this->dataProvider->type]);
        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Entry')), $route, ['class' => 'btn btn-primary']);
    }
    
    protected function getSelectionButtonItems(): array
    {
        if (!Yii::$app->getUser()->can(Entry::AUTH_ENTRY_UPDATE)) {
            return [];
        }

        return $this->statusSelectionButtonItems();
    }

    public function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Entry $entry) {
                $html = ($name = $entry->getI18nAttribute('name'))
                    ? Html::markKeywords(Html::encode($name), $this->search)
                    : Yii::t('cms', '[ No title ]');

                $html = Html::a($html, $this->getRoute($entry), [
                    'class' => $name ? 'strong' : 'strong text-muted',
                ]);

                if ($this->showUrl) {
                    $html .= $this->getUrl($entry);
                }

                if ($this->showCategories) {
                    $html .= $this->renderCategoryButtons($entry);
                }

                return $html;
            }
        ];
    }

    public function entryCountColumn(): array
    {
        return [
            'attribute' => 'entry_count',
            'class' => EntryCountColumn::class,
        ];
    }

    public function sectionCountColumn(): array
    {
        return [
            'attribute' => 'section_count',
            'class' => SectionCountColumn::class,
        ];
    }

    public function assetCountColumn(): array
    {
        return [
            'attribute' => 'asset_count',
            'class' => AssetCountColumn::class,
        ];
    }

    public function dateColumn(): array
    {
        // The Query object reflects the initial order even if Sort changed the query.
        return $this->dataProvider->query->orderBy && key($this->dataProvider->query->orderBy) === 'publish_date' ? $this->publishDateColumn() : $this->updatedAtColumn();
    }

    public function publishDateColumn(): array
    {
        return [
            'attribute' => 'publish_date',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => fn (Entry $entry) => $this->dateFormat ? $entry->publish_date->format($this->dateFormat) : Yii::$app->getFormatter()->asDate($entry->publish_date)
        ];
    }

    public function updatedAtColumn(): array
    {
        return [
            'attribute' => 'updated_at',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => fn (Entry $entry) => $this->dateFormat ? $entry->updated_at->format($this->dateFormat) : Timeago::tag($entry->updated_at)
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => fn (Entry $entry): string => Html::buttons($this->getRowButtons($entry))
        ];
    }

    protected function getRowButtons(Entry $entry): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($this->isSortedByPosition()
            && $this->dataProvider->getCount() > 1
            && $user->can(Entry::AUTH_ENTRY_ORDER)) {
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

    public function categoryDropdown(): string
    {
        if ($items = $this->categoryDropdownItems()) {
            return ButtonDropdown::widget([
                'label' => $this->dataProvider->category ? (Yii::t('cms', 'Category') . ': ' . Html::tag('strong', Html::encode($this->dataProvider->category->getI18nAttribute('name')))) : Yii::t('cms', 'Categories'),
                'showFilter' => $this->showCategoryDropdownFilterMinCount && $this->showCategoryDropdownFilterMinCount < count($items),
                'items' => $items,
                'paramName' => 'category',
            ]);
        }

        return '';
    }

    protected function categoryDropdownItems(): array
    {
        $items = [];

        foreach ($this->getCategories() as $category) {
            $items[] = [
                'label' => $this->getNestedCategoryNames()[$category->id],
                'url' => $category->hasEntriesEnabled() ? Url::current(['category' => $category->id, 'page' => null]) : null,
            ];
        }

        return $items;
    }

    public function renderCategoryButtons(Entry $entry, array $options = []): string
    {
        $categoryIds = $entry->getCategoryIds();
        $categories = [];

        foreach ($this->getCategories() as $category) {
            if ($category->hasEntriesEnabled() && in_array($category->id, $categoryIds)) {
                $categories[] = Html::a(Html::encode($category->getI18nAttribute('name')), Url::current(['category' => $category->id]), ['class' => 'btn btn-secondary btn-sm']);
            }
        }

        return $categories ? Html::tag('div', implode('', $categories), $options ?: ['class' => 'btn-list']) : '';
    }

    public function getCategories(): array
    {
        $this->_categories ??= Category::find()->indexBy('id')->all();
        return $this->_categories;
    }

    public function getNestedCategoryNames(): array
    {
        $this->_categoryNames ??= Category::indentNestedTree($this->getCategories(), Category::instance()->getI18nAttributeName('name'));
        return $this->_categoryNames;
    }

    public function getUrl(Entry $entry): string
    {
        if ($route = $entry->getRoute()) {
            $urlManager = Yii::$app->getUrlManager();
            $url = $entry->isEnabled() ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);

            if ($url) {
                return Html::tag('div', Html::a($url, $url, ['target' => '_blank']), ['class' => 'd-none d-md-block small']);
            }
        }

        return '';
    }

    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return array_merge(Yii::$app->getRequest()->get(), $model->getAdminRoute(), $params);
    }

    public function getModel(): Entry
    {
        return Entry::instance();
    }
}
