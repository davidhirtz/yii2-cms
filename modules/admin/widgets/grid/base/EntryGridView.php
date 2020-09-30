<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryCategoryController;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\CategoryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\timeago\Timeago;
use Yii;
use yii\helpers\Url;

/**
 * Class EntryGridView
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryGridView
 *
 * @property EntryActiveDataProvider $dataProvider
 */
class EntryGridView extends GridView
{
    use CategoryTrait;
    use ModuleTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;

    /**
     * @var bool whether entry urls should be displayed in the name column
     */
    public $showUrl = true;

    /**
     * @var bool whether category column should be visible when {@link EntryActiveDataProvider::$type} is null
     */
    public $showCategories = true;

    /**
     * @var bool whether categories should be selectable via dropdown
     */
    public $showCategoryDropdown = true;

    /**
     * @var int|false defines when dropdown filter text field is shown for category dropdown
     */
    public $showCategoryDropdownFilterMinCount = 50;

    /**
     * @var bool whether entry types should be selectable via dropdown
     */
    public $showTypeDropdown = true;

    /**
     * @var bool whether the delete button should be visible in the entry grid
     */
    public $showDeleteButton = false;

    /**
     * @var string
     */
    public $dateFormat;

    /**
     * @var array {@link \davidhirtz\yii2\cms\modules\admin\widgets\grid\EntryGridView::getNestedCategoryNames()}
     */
    private $_categoryNames;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->dataProvider->category) {
            /** {@link EntryCategoryController::actionOrder()} */
            $this->orderRoute = ['entry-category/order', 'category' => $this->dataProvider->category->id];
        }

        $enableCategories = static::getModule()->enableCategories && count(static::getCategories()) > 1;

        if ($this->showCategories) {
            $this->showCategories = $enableCategories;
        }

        if ($this->showCategoryDropdown) {
            $this->showCategoryDropdown = $enableCategories;
        }

        if ($enableCategories && $this->dataProvider->type) {
            $this->showCategories = Entry::getTypes()[$this->dataProvider->type]['showCategories'] ?? $this->showCategories;
            $this->showCategoryDropdown = Entry::getTypes()[$this->dataProvider->type]['showCategoryDropdown'] ?? $this->showCategoryDropdown;
        }

        if ($this->showTypeDropdown) {
            $this->showTypeDropdown = count(Entry::getTypes()) > 1;
        }

        $this->type = $this->dataProvider->type;

        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->typeColumn(),
                $this->nameColumn(),
                $this->sectionCountColumn(),
                $this->assetCountColumn(),
                $this->dateColumn(),
                $this->buttonsColumn(),
            ];
        }

        $this->initHeader();
        $this->initFooter();

        parent::init();
    }

    /**
     * Sets up grid header.
     */
    protected function initHeader()
    {
        if ($this->header === null) {
            $this->header = [
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
    }

    /**
     * Sets up grid footer.
     */
    protected function initFooter()
    {
        if ($this->footer === null) {
            $this->footer = [
                [
                    [
                        'content' => $this->getCreateEntryButton() . ($this->showSelection ? $this->getSelectionButton() : ''),
                        'options' => ['class' => 'col'],
                    ],
                ],
            ];
        }
    }

    /**
     * @return string
     */
    protected function getCreateEntryButton()
    {
        if (!Yii::$app->getUser()->can('entryCreate')) {
            return '';
        }

        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Entry')), ['create', 'type' => $this->dataProvider->type], ['class' => 'btn btn-primary']);
    }

    /**
     * @return array
     */
    protected function getSelectionButtonItems(): array
    {
        if (!Yii::$app->getUser()->can('entryUpdate')) {
            return [];
        }

        return $this->statusSelectionButtonItems();
    }

    /**
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Entry $entry) {
                $html = Html::markKeywords(Html::encode($entry->getI18nAttribute('name')), $this->search);
                $html = Html::tag('strong', Html::a($html, $this->getRoute($entry)));

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

    /**
     * @return array
     */
    public function sectionCountColumn()
    {
        return [
            'attribute' => 'section_count',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'visible' => static::getModule()->enableSections,
            'content' => function (Entry $entry) {
                return $entry->hasSectionsEnabled() ? Html::a(Yii::$app->getFormatter()->asInteger($entry->section_count), ['section/index', 'entry' => $entry->id], ['class' => 'badge']) : '';
            }
        ];
    }

    /**
     * @return array
     */
    public function assetCountColumn()
    {
        return [
            'attribute' => 'asset_count',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'visible' => static::getModule()->enableEntryAssets,
            'content' => function (Entry $entry) {
                return $entry->hasAssetsEnabled() ? Html::a(Yii::$app->getFormatter()->asInteger($entry->asset_count), array_merge($this->getRoute($entry), ['#' => 'assets']), ['class' => 'badge']) : '';
            }
        ];
    }

    /**
     * @return array
     */
    public function dateColumn()
    {
        // The Query object reflects the initial order even if Sort changed the query.
        return $this->dataProvider->query->orderBy && key($this->dataProvider->query->orderBy) === 'publish_date' ? $this->publishDateColumn() : $this->updatedAtColumn();
    }

    /**
     * @return array
     */
    public function publishDateColumn()
    {
        return [
            'attribute' => 'publish_date',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (Entry $entry) {
                return $this->dateFormat ? $entry->publish_date->format($this->dateFormat) : Yii::$app->getFormatter()->asDate($entry->publish_date);
            }
        ];
    }

    /**
     * @return array
     */
    public function updatedAtColumn()
    {
        return [
            'attribute' => 'updated_at',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (Entry $entry) {
                return $this->dateFormat ? $entry->updated_at->format($this->dateFormat) : Timeago::tag($entry->updated_at);
            }
        ];
    }

    /**
     * @return array
     */
    public function buttonsColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => function (Entry $entry) {
                return Html::buttons($this->getRowButtons($entry));
            }
        ];
    }

    /**
     * @param Entry $entry
     * @return array
     */
    protected function getRowButtons(Entry $entry)
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if($this->isSortedByPosition() && $this->dataProvider->getCount() > 1 && $user->can('entryOrder')) {
            $buttons[] = $this->getSortableButton();
        }

        if($user->can('entryUpdate', ['entry' => $entry])) {
            $buttons[] = $this->getUpdateButton($entry);
        }

        if($this->showDeleteButton && $user->can('entryDelete', ['entry' => $entry])) {
            $buttons[] = $this->getDeleteButton($entry);
        }

        return $buttons;
    }

    /**
     * @return string
     */
    public function categoryDropdown()
    {
        $categoryCount = count(static::getCategories());

        return !$categoryCount ? '' : ButtonDropdown::widget([
            'label' => $this->dataProvider->category ? (Yii::t('cms', 'Category') . ': ' . Html::tag('strong', Html::encode($this->dataProvider->category->getI18nAttribute('name')))) : Yii::t('cms', 'Categories'),
            'showFilter' => $this->showCategoryDropdownFilterMinCount && $this->showCategoryDropdownFilterMinCount < $categoryCount,
            'items' => $this->categoryDropdownItems(),
            'paramName' => 'category',
        ]);
    }

    /**
     * @return array
     */
    protected function categoryDropdownItems(): array
    {
        $items = [];

        foreach (static::getCategories() as $category) {
            $items[] = [
                'label' => $this->getNestedCategoryNames()[$category->id],
                'url' => $category->hasEntriesEnabled() ? Url::current(['category' => $category->id, 'page' => null]) : null,
            ];
        }

        return $items;
    }

    /**
     * @param Entry $entry
     * @param array $options
     * @return string
     */
    public function renderCategoryButtons(Entry $entry, $options = [])
    {
        $categories = [];

        foreach ($entry->getCategoryIds() as $categoryId) {
            if ($category = static::getCategories()[$categoryId] ?? false) {
                $categories[] = Html::a(Html::encode($category->getI18nAttribute('name')), Url::current(['category' => $category->id]), ['class' => 'btn btn-secondary btn-sm']);
            }
        }

        return $categories ? Html::tag('div', implode('', $categories), $options ?: ['class' => 'btn-list']) : '';
    }

    /**
     * @param Entry $entry
     * @return string
     */
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

    /**
     * @param ActiveRecord $model
     * @param array $params
     * @return array
     */
    protected function getRoute(ActiveRecord $model, $params = []): array
    {
        /** @var Entry $model */
        return array_merge(Yii::$app->getRequest()->get(), ['/admin/entry/update', 'id' => $model->id], $params);
    }

    /**
     * @return array|mixed
     */
    public function getNestedCategoryNames(): array
    {
        if ($this->_categoryNames === null) {
            $this->_categoryNames = Category::indentNestedTree(static::getCategories(), Category::instance()->getI18nAttributeName('name'));
        }

        return $this->_categoryNames;
    }

    /**
     * @return Entry
     */
    public function getModel()
    {
        return Entry::instance();
    }
}