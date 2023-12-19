<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\data\CategoryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\CounterColumn;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use Yii;
use yii\helpers\Url;

/**
 * @property CategoryActiveDataProvider $dataProvider
 */
trait CategoryGridTrait
{
    use StatusGridViewTrait;
    use TypeGridViewTrait;
    use ModuleTrait;

    /**
     * @var string|null the format used to format the date values.
     */
    public ?string $dateFormat = null;

    protected function initHeader(): void
    {
        if ($this->header === null) {
            $this->header = [
                [
                    [
                        'content' => $this->categoryDropdown(),
                        'options' => ['class' => 'col-12 col-md-3'],
                    ],
                    [
                        'content' => $this->getSearchInput(),
                        'options' => ['class' => 'col-12 col-md-6'],
                    ],
                    'options' => [
                        'class' => $this->dataProvider->category ? 'justify-content-between' : 'justify-content-end',
                    ],
                ],
            ];
        }
    }

    public function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Category $category) {
                $html = ($name = $category->getI18nAttribute('name'))
                    ? Html::markKeywords(Html::encode($name), $this->search)
                    : Yii::t('cms', '[ No title ]');

                $html = Html::a($html, $this->getRoute($category), [
                    'class' => $name ? 'strong' : 'strong text-muted',
                ]);

                if ($this->showCategoryAncestors($category)) {
                    $html .= Html::tag('div', $this->getCategoryAncestors($category), ['class' => 'small']);
                }

                if ($this->showUrl) {
                    $html .= $this->getUrl($category);
                }

                return $html;
            }
        ];
    }

    public function branchCountColumn(): array
    {
        return [
            'attribute' => 'branchCount',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'visible' => static::getModule()->enableNestedCategories,
            'content' => function (Category $category) {
                $route = [$this->categoryParamName => $category->id, 'page' => null, 'q' => null];
                return Html::a(Yii::$app->getFormatter()->asInteger($category->getBranchCount()), Url::current($route), ['class' => 'badge']);
            }
        ];
    }

    public function entryCountColumn(): array
    {
        return [
            'class' => CounterColumn::class,
            'attribute' => 'entry_count',
            'route' => fn (Category $category) => ['entry/index', 'category' => $category->id],
            'value' => fn (Category $category) => $category->hasEntriesEnabled() ? $category->entry_count : null,
        ];
    }

    public function getUrl(Category $category): string
    {
        if ($route = $category->getRoute()) {
            $urlManager = Yii::$app->getUrlManager();
            $url = $category->isEnabled() ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);

            if ($url) {
                return Html::tag('div', Html::a($url, $url, ['target' => '_blank']), ['class' => 'd-none d-md-block small']);
            }
        }

        return '';
    }

    protected function getCategoryAncestors(Category $category): string
    {
        if ($category->parent_id) {
            $parents = [];

            foreach ($category->getAncestors() as $parent) {
                $parents[] = Html::a(Html::encode($parent->name), $this->getRoute($parent));
            }

            return implode(' / ', $parents);
        }

        return '';
    }

    public function showCategoryAncestors(Category $category): bool
    {
        return $this->dataProvider->searchString || $category->entryCategory;
    }

    protected function categoryDropdown(array $config = []): ?string
    {
        if ($category = $this->dataProvider->category) {
            $config['label'] = Html::tag('strong', Html::encode($category->getI18nAttribute('name')));
            $config['paramName'] = 'id';

            $categories = Category::indentNestedTree($category->getAncestors() + [$category], Category::instance()->getI18nAttributeName('name'));

            foreach ($categories as $id => $name) {
                $config['items'][] = [
                    'label' => $name,
                    'url' => Url::current([$this->categoryParamName => $id, 'page' => null]),
                ];
            }

            return ButtonDropdown::widget($config);
        }

        return null;
    }

    /**
     * Sets ancestors for all categories to avoid each record loading its ancestors from the database.
     * If no parent category is set simply set all loaded models and let {@see Category::setAncestors}
     * work it's magic.
     */
    protected function initAncestors(): void
    {
        if ($this->dataProvider->category) {
            $categories = [$this->dataProvider->category->id => $this->dataProvider->category] + $this->dataProvider->category->ancestors;
        } else {
            $categories = !$this->dataProvider->searchString ? $this->dataProvider->getModels() : Category::find()
                ->indexBy('id')
                ->all();
        }

        foreach ($this->dataProvider->getModels() as $category) {
            $category->setAncestors($categories);
        }
    }

    public function getModel(): Category
    {
        return Category::instance();
    }
}
